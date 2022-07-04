<?php


class Lecture implements \JsonSerializable
{
    private int $id;
    private float $minutes;
    private bool $dontLeft =false;




    /**
     * Lecture constructor.
     * @param int $id
     * @param float $minutes
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }




    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return float
     */
    public function getMinutes(): float
    {
        return $this->minutes;
    }

    /**
     * @param float $minutes
     */
    public function setMinutes(float $minutes): void
    {
        $this->minutes = $minutes;
    }


    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
    /**
     * @return bool
     */
    public function isDontLeft(): bool
    {
        return $this->dontLeft;
    }

    /**
     * @param bool $dontLeft
     */
    public function setDontLeft(bool $dontLeft): void
    {
        $this->dontLeft = $dontLeft;
    }

}