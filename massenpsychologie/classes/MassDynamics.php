<?php
/**
 * Implementierung des mass_dynamics-Algorithmus:
 *
 * function mass_dynamics(event):
 *     individuals = observe(event)
 *     for person in individuals:
 *         reaction = observe_others(person)
 *         group = assign_identity(person, reaction)
 *         person.opinion = conform_to(group)
 *         amplify_emotions(group)
 *         if anonymity_high: reduce_inhibition(person)
 *     norms = emerge(group)
 *     if norms == extreme: escalate(group) else: stabilize(group)
 *     return outcome
 */
class MassDynamics {

    private array $logs = [];

    // observe_others: gewichteter Durchschnitt der 5 ähnlichsten Nachbarn
    private function observeOthers(Agent $person, array $all): float {
        $distances = [];
        foreach ($all as $other) {
            if ($other->id === $person->id) continue;
            $dist = abs($person->opinion - $other->opinion)
                  + abs($person->emotional_state - $other->emotional_state);
            $distances[$other->id] = ['dist' => $dist, 'opinion' => $other->opinion];
        }
        usort($distances, fn($a, $b) => $a['dist'] <=> $b['dist']);
        $neighbors = array_slice($distances, 0, 5);
        if (empty($neighbors)) return $person->opinion;
        return array_sum(array_column($neighbors, 'opinion')) / count($neighbors);
    }

    // assign_identity: Zuweisung zur nächsten Gruppe per Opinion-Nähe
    private function assignIdentity(Agent $person, float $reaction, array &$groups): ?Group {
        $best = null;
        $bestDist = PHP_FLOAT_MAX;
        foreach ($groups as $group) {
            $dist = abs($group->average_opinion - $reaction);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $group;
            }
        }
        return $best;
    }

    // conform_to: Meinung bewegt sich 30% in Richtung Gruppendurchschnitt
    private function conformTo(Group $group, Agent $person): float {
        $delta = $group->average_opinion - $person->opinion;
        return $person->opinion + CONFORM_WEIGHT * $delta * (1 - $person->inhibition_level);
    }

    // amplify_emotions: Gruppenemotionen steigen um Faktor EMOTION_AMPLIFY
    private function amplifyEmotions(Group $group): void {
        foreach ($group->members as $member) {
            $member->emotional_state = min(1.0, $member->emotional_state * EMOTION_AMPLIFY);
        }
    }

    // reduce_inhibition: Anonymität senkt die Hemmungsschwelle
    private function reduceInhibition(Agent $person): void {
        $person->inhibition_level = max(0.0, $person->inhibition_level - INHIBITION_STEP);
    }

    // emerge: Norm = Median der Gruppenopinionen
    private function emerge(Group $group): float {
        if (empty($group->members)) return 0.0;
        $opinions = array_map(fn($a) => $a->opinion, $group->members);
        sort($opinions);
        $n = count($opinions);
        return $n % 2 === 0
            ? ($opinions[$n / 2 - 1] + $opinions[$n / 2]) / 2
            : $opinions[(int)($n / 2)];
    }

    // escalate: Emotionen und Meinungsextremität verstärken sich
    private function escalate(Group $group): void {
        $group->escalated = true;
        foreach ($group->members as $member) {
            $member->opinion = $member->opinion > 0
                ? min(1.0, $member->opinion + 0.1)
                : max(-1.0, $member->opinion - 0.1);
            $member->emotional_state = min(1.0, $member->emotional_state + 0.15);
        }
    }

    // stabilize: Meinungen konvergieren leicht zur Mitte
    private function stabilize(Group $group): void {
        $group->escalated = false;
        foreach ($group->members as $member) {
            $member->opinion *= 0.95;
            $member->emotional_state = max(0.1, $member->emotional_state * 0.9);
        }
    }

    /**
     * Führt eine vollständige Simulationsrunde aus.
     * Gibt die aktualisierten Agenten + Gruppen + Logs zurück.
     */
    public function runRound(array $agents, array $groups, int $simId, int $round): array {
        $this->logs = [];

        // Gruppenmitglieder zuweisen
        foreach ($groups as $group) {
            $group->members = [];
        }
        foreach ($agents as $agent) {
            if ($agent->group_id !== null) {
                foreach ($groups as $group) {
                    if ($group->id === $agent->group_id) {
                        $group->members[] = $agent;
                        break;
                    }
                }
            }
        }

        // Hauptschleife: for person in individuals
        foreach ($agents as $agent) {
            $oldOpinion = $agent->opinion;

            // reaction = observe_others(person)
            $reaction = $this->observeOthers($agent, $agents);

            // group = assign_identity(person, reaction)
            $group = $this->assignIdentity($agent, $reaction, $groups);

            // Gruppe neu zuweisen wenn nötig
            if ($group && $agent->group_id !== $group->id) {
                // aus alter Gruppe entfernen
                foreach ($groups as $g) {
                    $g->members = array_values(array_filter($g->members, fn($m) => $m->id !== $agent->id));
                }
                $agent->group_id = $group->id;
                $group->members[] = $agent;
            }

            if (!$group) continue;

            // person.opinion = conform_to(group)
            $agent->opinion = $this->conformTo($group, $agent);
            $agent->opinion = max(-1.0, min(1.0, $agent->opinion));

            // amplify_emotions(group)
            $this->amplifyEmotions($group);

            // if anonymity_high: reduce_inhibition(person)
            if ($agent->anonymity) {
                $this->reduceInhibition($agent);
            }

            $this->logs[] = [
                'simulation_id' => $simId,
                'round'         => $round,
                'agent_id'      => $agent->id,
                'action'        => 'opinion_update',
                'old_opinion'   => $oldOpinion,
                'new_opinion'   => $agent->opinion,
                'group_id'      => $agent->group_id,
                'data'          => json_encode(['reaction' => $reaction, 'anonymity' => $agent->anonymity]),
            ];
        }

        // Gruppen-Statistiken neu berechnen
        foreach ($groups as $group) {
            $group->recalculate();
        }

        // norms = emerge(group); escalate oder stabilize
        foreach ($groups as $group) {
            if (empty($group->members)) continue;
            $norm = $this->emerge($group);
            if (abs($norm) >= EXTREME_THRESHOLD) {
                $this->escalate($group);
                $this->logs[] = [
                    'simulation_id' => $simId,
                    'round'         => $round,
                    'agent_id'      => null,
                    'action'        => 'escalate',
                    'old_opinion'   => null,
                    'new_opinion'   => null,
                    'group_id'      => $group->id,
                    'data'          => json_encode(['norm' => $norm]),
                ];
            } else {
                $this->stabilize($group);
                $this->logs[] = [
                    'simulation_id' => $simId,
                    'round'         => $round,
                    'agent_id'      => null,
                    'action'        => 'stabilize',
                    'old_opinion'   => null,
                    'new_opinion'   => null,
                    'group_id'      => $group->id,
                    'data'          => json_encode(['norm' => $norm]),
                ];
            }
        }

        return [
            'agents' => $agents,
            'groups' => $groups,
            'logs'   => $this->logs,
        ];
    }

    public function getLogs(): array { return $this->logs; }
}
