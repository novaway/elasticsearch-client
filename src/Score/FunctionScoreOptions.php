<?php


namespace Novaway\ElasticsearchClient\Score;


class FunctionScoreOptions
{
    /** @var string */
    protected $scoreMode;
    /** @var string */
    protected $boostMode;
    /** @var int */
    protected $boost;
    /** @var int */
    protected $maxBoost;
    /** @var int */
    protected $minBoost;

    public function __construct(string $scoreMode = null, string $boostMode = BoostMode::REPLACE, int $boost = null, int $maxBoost = null, int $minBoost = null)
    {
        if ($scoreMode && !in_array($scoreMode, ScoreMode::$available)) {
            throw new \InvalidArgumentException('$scoreMode should be one of ' . implode(",", ScoreMode::$available) . ". $scoreMode given");
        }
        if ($boostMode && !in_array($boostMode, BoostMode::$available)) {
            throw new \InvalidArgumentException('$boostMode should be one of ' . implode(",", BoostMode::$available) . ". $boostMode given");
        }

        $this->scoreMode = $scoreMode;
        $this->boostMode = $boostMode;
        $this->boost = $boost;
        $this->maxBoost = $maxBoost;
        $this->minBoost = $minBoost;
    }


    public function formatForQuery(): array
    {
        $res = [];
        if (null !== $this->scoreMode) {
            $res['score_mode'] = $this->scoreMode;
        }
        if (null !== $this->boostMode) {
            $res['boost_mode'] = $this->boostMode;
        }
        if (null !== $this->boost) {
            $res['boost'] = $this->boost;
        }
        if (null !== $this->maxBoost) {
            $res['max_boost'] = $this->maxBoost;
        }
        if (null !== $this->maxBoost) {
            $res['min_boost'] = $this->minBoost;
        }
        return $res;
    }
}