<?php

namespace Novaway\ElasticsearchClient\Query;


final class BoostMode
{
    // Multiply the _score with the function result (default)
    const MULTIPLY = "multiply";
    // Add the function result to the _score
    const SUM = "sum";
    // The lower of the _score and the function result
    const MIN = "min";
    // The higher of the _score and the function result
    const MAX = "max";
    // Replace the _score with the function result
    const REPLACE = "replace";
}