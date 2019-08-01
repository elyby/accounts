<?php
declare(strict_types=1);

namespace api\components\Tokens;

use api\components\Tokens\Algorithms\AlgorithmInterface;
use Webmozart\Assert\Assert;

class AlgorithmsManager {

    /**
     * @var AlgorithmInterface[]
     */
    private $algorithms = [];

    public function __construct(array $algorithms = []) {
        array_map([$this, 'add'], $algorithms);
    }

    public function add(AlgorithmInterface $algorithm): self {
        $id = $algorithm->getAlgorithmId();
        Assert::keyNotExists($this->algorithms, $id, 'passed algorithm is already exists');
        $this->algorithms[$algorithm->getSigner()->getAlgorithmId()] = $algorithm;

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
