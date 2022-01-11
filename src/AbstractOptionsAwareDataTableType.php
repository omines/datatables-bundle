<?php
declare(strict_types=1);

namespace Omines\DataTablesBundle;

use Symfony\Component\OptionsResolver\OptionsResolver;


abstract class AbstractOptionsAwareDataTableType implements OptionsAwareDataTableTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }
}