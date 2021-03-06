<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Tests\Listener;

use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

class WorkFlowSubscriberTest extends TestCase
{
    use DoctrineTestTrait,
        MockeryPHPUnitIntegration;

    public function testInvoicePaid()
    {
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->once();

        $subscriber = new WorkFlowSubscriber($this->registry, $notification);

        $invoice = (new Invoice())
            ->setStatus('pending')
            ->setBalance(new Money(1200, new Currency('USD')));

        $subscriber->onWorkflowTransitionApplied(new Event($invoice, new Marking(['pending' => 1]), new Transition('pay', 'pending', 'paid')));
        $this->assertNotNull($invoice->getPaidDate());
        $this->assertEquals($invoice, $this->em->getRepository(Invoice::class)->find(1));
    }

    public function testInvoiceArchive()
    {
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->once();

        $subscriber = new WorkFlowSubscriber($this->registry, $notification);

        $invoice = (new Invoice())
            ->setStatus('pending')
            ->setBalance(new Money(1200, new Currency('USD')));

        $subscriber->onWorkflowTransitionApplied(new Event($invoice, new Marking(['pending' => 1]), new Transition('archive', 'pending', 'archived')));

        $this->assertTrue($invoice->isArchived());
        $this->assertSame($invoice, $this->em->getRepository(Invoice::class)->find(1));
    }

    public function getEntityNamespaces()
    {
        return [
            'SolidInvoiceInvoiceBundle' => 'SolidInvoice\\InvoiceBundle\\Entity',
        ];
    }

    public function getEntities()
    {
        return [
            Invoice::class,
            RecurringInvoice::class,
        ];
    }
}
