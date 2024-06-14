<?php
declare(strict_types=1);

namespace api\components\Tokens;

use api\components\Tokens\Algorithms\AlgorithmInterface;
use Webmozart\Assert\Assert;

/**
 * This class is used to hold multiple keys signing mechanisms.
 * This may be useful when we change the key signing algorithm to allow during the transition period
 * the keys with both algorithms to work simultaneously.
 */
final class AlgorithmsManager {

    /**
     * @var AlgorithmInterface[]
     */
    private array $algorithms = [];

    /**
     * @param AlgorithmInterface[] $algorithms
     */
    public function __construct(array $algorithms = []) {
        array_map([$this, 'add'], $algorithms);
    }

    public function add(AlgorithmInterface $algorithm): self {
        $id = $algorithm->getSigner()->getAlgorithmId();
        Assert::keyNotExists($this->algorithms, $id, 'passed algorithm is already exists');
        $this->algorithms[$id] = $algorithm;

        return $this;
    }

    /**
     * @param string $algorithmId
     *
     * @return AlgorithmInterface
     * @throws AlgorithmIsNotDefinedException
     */
    public function get(string $algorithmId): AlgorithmInterface {
        if (!isset($this->algorithms[$algorithmId])) {
            throw new AlgorithmIsNotDefinedException($algorithmId);
        }

        return $this->algorithms[$algorithmId];
    }

}
