<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

#[ORM\Entity(repositoryClass: \Gedmo\Loggable\Entity\Repository\LogEntryRepository::class)]
#[ORM\Table(name: 'ext_log_entries')]
#[ORM\Index(columns: ['object_class'], name: 'log_class_lookup_idx')]
#[ORM\Index(columns: ['logged_at'], name: 'log_date_lookup_idx')]
#[ORM\Index(columns: ['username'], name: 'log_user_lookup_idx')]
#[ORM\Index(columns: ['object_id', 'object_class', 'version'], name: 'log_version_lookup_idx')]
class PostLogEntry extends AbstractLogEntry
{
    #[ORM\Column(type: 'json', nullable: true)]
    protected $data;
}
