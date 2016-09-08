<?php
/**
 * Automatically adds and removes terms to content that supports TaxonomyOwnerInterface.
 */

namespace SymfonyContrib\Bundle\TaxonomyBundle\Doctrine;

use Doctrine\Common\EventSubscriber as EventSubscriberInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use SymfonyContrib\Bundle\TaxonomyBundle\Model\TaxonomyOwnerInterface;

class EventSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::preRemove,
        ];
    }

    /**
     * Map terms when new entities are inserted.
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        // Ensure entity supports taxonomy.
        if ($entity instanceof TaxonomyOwnerInterface) {
            $taxonomy = $entity->getTaxonomy();
            $terms    = $entity->getTerms();
            foreach ($terms as $vocab) {
                foreach ($vocab as $term) {
                    $taxonomy->mapTerm($term, $entity);
                }
            }
        }
    }

    /**
     * Delete term maps when entities are removed.
     *
     * postRemove event does not pass the entity primary key.
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        // Ensure entity supports taxonomy.
        if ($entity instanceof TaxonomyOwnerInterface) {
            $entity->removeAllTerms();
        }
    }
}
