<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\Entities;

class Branch
{
    /**
     * @var string
     */
    private $name;

    /**
     * Constructor
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
