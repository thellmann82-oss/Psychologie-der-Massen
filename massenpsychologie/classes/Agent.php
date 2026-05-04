<?php
class Agent {
    public int    $id;
    public int    $project_id;
    public string $name;
    public string $personality_type;
    public string $bio;
    public float  $opinion;          // -1.0 (extrem dagegen) bis +1.0 (extrem dafür)
    public float  $emotional_state;  // 0.0 (ruhig) bis 1.0 (hocherregt)
    public float  $inhibition_level; // 0.0 (keine Hemmung) bis 1.0 (stark gehemmt)
    public bool   $anonymity;
    public ?int   $group_id;

    public function __construct(array $row) {
        $this->id               = (int)$row['id'];
        $this->project_id       = (int)$row['project_id'];
        $this->name             = $row['name'];
        $this->personality_type = $row['personality_type'];
        $this->bio              = $row['bio'];
        $this->opinion          = (float)$row['opinion'];
        $this->emotional_state  = (float)$row['emotional_state'];
        $this->inhibition_level = (float)$row['inhibition_level'];
        $this->anonymity        = (bool)$row['anonymity'];
        $this->group_id         = isset($row['group_id']) ? (int)$row['group_id'] : null;
    }

    public static function allForProject(int $projectId): array {
        $db   = Database::get();
        $stmt = $db->prepare('SELECT * FROM agents WHERE project_id = ?');
        $stmt->execute([$projectId]);
        return array_map(fn($r) => new self($r), $stmt->fetchAll());
    }

    public function save(): void {
        $db = Database::get();
        $db->prepare(
            'UPDATE agents SET opinion=?, emotional_state=?, inhibition_level=?, group_id=? WHERE id=?'
        )->execute([$this->opinion, $this->emotional_state, $this->inhibition_level, $this->group_id, $this->id]);
    }

    public function toArray(): array {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'personality_type' => $this->personality_type,
            'bio'              => $this->bio,
            'opinion'          => round($this->opinion, 4),
            'emotional_state'  => round($this->emotional_state, 4),
            'inhibition_level' => round($this->inhibition_level, 4),
            'anonymity'        => $this->anonymity,
            'group_id'         => $this->group_id,
        ];
    }
}
