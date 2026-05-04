<?php
class Group {
    public int    $id;
    public int    $project_id;
    public string $name;
    public float  $average_opinion;
    public float  $norm_extremity;
    public int    $size;
    public bool   $escalated;

    /** @var Agent[] */
    public array $members = [];

    public function __construct(array $row) {
        $this->id              = (int)$row['id'];
        $this->project_id      = (int)$row['project_id'];
        $this->name            = $row['name'];
        $this->average_opinion = (float)$row['average_opinion'];
        $this->norm_extremity  = (float)$row['norm_extremity'];
        $this->size            = (int)$row['size'];
        $this->escalated       = (bool)$row['escalated'];
    }

    public static function forProject(int $projectId): array {
        $db   = Database::get();
        $stmt = $db->prepare('SELECT * FROM groups WHERE project_id = ?');
        $stmt->execute([$projectId]);
        return array_map(fn($r) => new self($r), $stmt->fetchAll());
    }

    // Berechnet Durchschnittsmeinung und Norm-Extremität aus den Mitgliedern
    public function recalculate(): void {
        if (empty($this->members)) return;
        $opinions            = array_map(fn($a) => $a->opinion, $this->members);
        $this->average_opinion = array_sum($opinions) / count($opinions);
        $this->norm_extremity  = abs($this->average_opinion);
        $this->size            = count($this->members);
    }

    public function save(): void {
        Database::get()->prepare(
            'UPDATE groups SET average_opinion=?, norm_extremity=?, size=?, escalated=? WHERE id=?'
        )->execute([$this->average_opinion, $this->norm_extremity, $this->size, $this->escalated ? 1 : 0, $this->id]);
    }

    public function toArray(): array {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'average_opinion' => round($this->average_opinion, 4),
            'norm_extremity'  => round($this->norm_extremity, 4),
            'size'            => $this->size,
            'escalated'       => $this->escalated,
        ];
    }
}
