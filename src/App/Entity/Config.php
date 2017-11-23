<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * OeilActivities
 *
 * @ORM\Table(name="fb_config")
 * @ORM\Entity
 */
class Config
{
    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=512, nullable=false)
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="verify", type="string", length=256, nullable=false)
     */
    private $verify;

}

