<?php

namespace WorkspaceTest\Models\LogRevision;

use Doctrine\ORM\Mapping\ClassMetadata
    , Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
    , Doctrine\Common\Collections\ArrayCollection
    ;

class Performer {

    private $id;
    private $name;
    private $albums;

    public function getId()
    {
        return $this->id;
    }

    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAlbums()
    {
        if (!$this->albums)
            $this->albums = new ArrayCollection();

        return $this->albums;
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->createField('id', 'integer')->isPrimaryKey()->generatedValue()->build();
        $builder->addField('name', 'string');
        $builder->addOwningManyToMany('albums', 'Album', 'performers');
    }
}
