<?php

namespace EVT\CoreDomainBundle\Test\Functional\Repository;

use EVT\CoreDomain\Email;
use EVT\CoreDomain\Lead\Lead;
use EVT\CoreDomain\Lead\LeadId;
use EVT\CoreDomain\Lead\Event;
use EVT\CoreDomain\Lead\EventType;
use EVT\CoreDomain\Lead\Location;
use EVT\CoreDomain\User\PersonalInformation;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * LeadRepositoryTest
 *
 * @author    Quique Torras <etorras@bodaclick.com>
 *
 * @copyright 2014 Bodaclick S.A.
 */
class LeadRepositoryTest extends WebTestCase
{
    private $repo;

    public function setUp()
    {
        $classes = [
            'EVT\ApiBundle\Tests\DataFixtures\ORM\LoadEmployeeData',
            'EVT\ApiBundle\Tests\DataFixtures\ORM\LoadLeadData',
            'EVT\ApiBundle\Tests\DataFixtures\ORM\LoadNotMainLeadData'
        ];
        $this->loadFixtures($classes);
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->repo = static::$kernel->getContainer()->get('evt.repository.lead');
    }

    public function testFindByIdOwner()
    {
        $lead = $this->repo->findByIdOwner(1, 'usernameManager');

        $this->assertInstanceOf('EVT\CoreDomain\Lead\Lead', $lead);
        $this->assertEquals('valid@email.com', $lead->getEmail()->getEmail());
    }

    public function providerLead()
    {
        return [
            [1, 'username'],
            [1, 'usernameManager2'],
            [5, 'usernameManager'],
        ];
    }

    /**
     * @dataProvider providerLead
     */
    public function testFindByIdOwnerKO($id, $username)
    {
        $lead = $this->repo->findByIdOwner($id, $username);

        $this->assertNull($lead);
    }

    public function testFindByOwner()
    {
        $leads = $this->repo->findByOwner(new ParameterBag(['canView' => 'usernameManager']));

        $this->assertCount(2, $leads->getItems());
        $this->assertInstanceOf('EVT\CoreDomain\Lead\Lead', $leads->getItems()[0]);
        $this->assertEquals('valid@email.com', $leads->getItems()[0]->getEmail()->getEmail());
        $this->assertEquals(1, $leads->getPagination()['total_pages']);
        $this->assertEquals(1, $leads->getPagination()['current_page']);
        $this->assertEquals(10, $leads->getPagination()['items_per_page']);
        $this->assertEquals(2, $leads->getPagination()['total_items']);
    }

    public function providerLeads()
    {
        return [
            ['username'],
            ['usernameManager2']
        ];
    }

    /**
     * @dataProvider providerLeads
     */
    public function testFindByOwnerKO($username)
    {
        $lead = $this->repo->findByOwner(new ParameterBag(['canView' => $username]));

        $this->assertNull($lead);
    }

    public function providerLeadsWrongPage()
    {
        return [
            ['username', 0],
            ['username', ''],
            ['username', '-'],
            ['username', 'a'],
        ];
    }

    /**
     *
     * @dataProvider providerLeadsWrongPage
     * @expectedException InvalidArgumentException
     */
    public function testFindByOwnerWrongPage($username, $page)
    {
        $lead = $this->repo->findByOwner(new ParameterBag(['page' => $page, 'canView' => $username]));
    }

    public function testSaveCreate()
    {
        $repoShowroom = static::$kernel->getContainer()
            ->get('evt.repository.showroom');

        $email = new Email('email@mail.com');
        $personalInfo = new PersonalInformation('a', 'b', 'c');
        $showroom = $repoShowroom->findOneById(1);
        $event = new Event(
            new EventType(EventType::BIRTHDAY),
            new Location(0, -10.6754, 'Madrid', 'Madrid', 'Spain'),
            new \DateTime('now')
        );

        $lead = new Lead(new LeadId(''), $personalInfo, $email, $showroom, $event);
        $numLeads = $this->repo->count();

        $this->repo->save($lead);
        $leadCheck = $this->repo->findByIdOwner($lead->getId(), 'usernameManager');

        $this->assertInstanceOf('EVT\CoreDomain\Lead\Lead', $leadCheck);
        $this->assertEquals('email@mail.com', $leadCheck->getEmail()->getEmail());
        $this->assertEquals(($numLeads + 1), $this->repo->count());
        $this->assertEquals(0, $leadCheck->getEvent()->getLocation()->getLatLong()['lat']);
        $this->assertEquals(-10.6754, $leadCheck->getEvent()->getLocation()->getLatLong()['long']);
    }

    public function testSaveUpdate()
    {
        $lead = $this->repo->findByIdOwner(1, 'usernameManager');
        $oldDate = $lead->getReadAt();
        $lead->read();

        $numLeads = $this->repo->count();

        $this->repo->save($lead);

        $lead = $this->repo->findByIdOwner(1, 'usernameManager');

        $this->assertInstanceOf('EVT\CoreDomain\Lead\Lead', $lead);
        $this->assertNotEquals($oldDate, $lead->getReadAt());
        $this->assertEquals($numLeads, $this->repo->count());
    }

    public function testGetLastLeadByEmail()
    {
        $lead = $this->repo->getLastLeadByEmail('valid@email.com');

        $this->assertInstanceOf('EVT\CoreDomain\Lead\Lead', $lead);
        $this->assertEquals('valid@email.com', $lead->getEmail()->getEmail());
        $this->assertEquals(2, $lead->getId());
        $this->assertEquals(new \DateTime('2013-11-11 00:00:00'), $lead->getCreatedAt());
    }

    public function testGetLastLeadByEmailNoExistsEmail()
    {
        $lead = $this->repo->getLastLeadByEmail('noexiste@email.com');

        $this->assertNull($lead);
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->repo->count());
    }

    public function testFindByShowroomEmailSeconds()
    {
        $repoShowroom = static::$kernel->getContainer()
            ->get('evt.repository.showroom');

        $email = new Email('rare@mail.com');
        $personalInfo = new PersonalInformation('a', 'b', 'c');
        $showroom = $repoShowroom->findOneById(1);
        $event = new Event(
            new EventType(EventType::BIRTHDAY),
            new Location(10, 10, 'Madrid', 'Madrid', 'Spain'),
            new \DateTime('now')
        );

        $lead = new Lead(new LeadId(''), $personalInfo, $email, $showroom, $event);

        $this->repo->save($lead);

        $leads = $this->repo->findByShowroomEmailSeconds($showroom, 'rare@mail.com', 60);

        $this->assertCount(1, $leads);
        $this->assertEquals('rare@mail.com', $leads[0]->getEmail()->getEmail());
    }

    public function testFindByOwnerEmployee()
    {
        $leads = $this->repo->findByOwner(new ParameterBag(['canView' => 'usernameEmployee']));

        $this->assertCount(3, $leads->getItems());
        $this->assertInstanceOf('EVT\CoreDomain\Lead\Lead', $leads->getItems()[0]);
        $this->assertEquals('validOther@email.com', $leads->getItems()[0]->getEmail()->getEmail());
        $this->assertEquals(1, $leads->getPagination()['total_pages']);
        $this->assertEquals(1, $leads->getPagination()['current_page']);
        $this->assertEquals(10, $leads->getPagination()['items_per_page']);
        $this->assertEquals(3, $leads->getPagination()['total_items']);
    }

    public function testFindByOwnerEmployeeFilter()
    {
        $leads = $this->repo->findByOwner(new ParameterBag(
            [
                'canView' => 'usernameEmployee',
                'vertical' => 'test.com',
                'location_level2' => 'Madrid',
                'location_level1' => 'Madrid',
                'event_type' => 1,
                'create_start' => '2013-10-10',
                'create_end' => '2013-11-11',
                'provider' => 'name',
                'lead_status' => 'read',
                'event_start' => '2014-01-25',
                'event_end' => '2014-02-20'
            ]
        ));

        $this->assertCount(2, $leads->getItems());
        $this->assertInstanceOf('EVT\CoreDomain\Lead\Lead', $leads->getItems()[0]);
        $this->assertEquals('valid@email.com', $leads->getItems()[0]->getEmail()->getEmail());
        $this->assertEquals(1, $leads->getPagination()['total_pages']);
        $this->assertEquals(1, $leads->getPagination()['current_page']);
        $this->assertEquals(10, $leads->getPagination()['items_per_page']);
        $this->assertEquals(2, $leads->getPagination()['total_items']);
    }

    public function testFindByOwnerEmployeeFilterZeroResult()
    {
        $leads = $this->repo->findByOwner(new ParameterBag(
            [
                'canView' => 'usernameEmployee',
                'vertical' => 'test.com',
                'location_level2' => 'Madrid',
                'location_level1' => 'Madrid',
                'event_type' => 1,
                'create_start' => '2013-10-10',
                'create_end' => '2013-11-11',
                'provider' => 'name',
                'lead_status' => 'unread',
                'event_start' => '2014-01-25',
                'event_end' => '2014-02-20'
            ]
        ));

        $this->assertNull($leads);
    }
}
