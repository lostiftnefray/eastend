<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Prop", mappedBy="product_id", orphanRemoval=false)
     */
    private $props;

    public function __construct()
    {
        $this->props = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Prop[]
     */
    public function getProps(): Collection
    {
        return $this->props;
    }

    public function addProp(Prop $prop): self
    {
        if (!$this->props->contains($prop)) {
            $this->props[] = $prop;
            $prop->setProductId($this);
        }

        return $this;
    }

    public function removeProp(Prop $prop): self
    {
        if ($this->props->contains($prop)) {
            $this->props->removeElement($prop);
            // set the owning side to null (unless already changed)
            if ($prop->getProductId() === $this) {
                $prop->setProductId(null);
            }
        }

        return $this;
    }
}
