<?php

namespace EVT\CoreDomain\Provider;

use EVT\CoreDomain\InformationBag;

/**
 * Vertical
 *
 * @author    Marco Ferrari <marco.ferrari@bodaclick.com>
 * @copyright 2014 Bodaclick S.A
 */
class Vertical
{
    private $domain;
    private $showrooms;

    public function __construct($domain)
    {
        $this->domain = $domain;
        $this->showrooms = new \ArrayObject();
    }

    public function addShowroom(Provider $provider, $type, InformationBag $infoBag = null, $extra_data = '')
    {
        $iterator = new ShowroomsProviderFilter($this->showrooms->getIterator(), $provider);

        if (iterator_count($iterator) == 0) {
            $showroom = new Showroom($provider, $this, $type, $infoBag, $extra_data);
            $this->showrooms->append($showroom);
            return $showroom;
        }
        foreach ($iterator as $showroom) {
            return $showroom;
        }
    }

    public function getDomain()
    {
        return $this->domain;
    }
}
