<?php

declare(strict_types=1);

namespace Netgen\ContentBrowser\Form\Type;

use Netgen\ContentBrowser\Exceptions\NotFoundException;
use Netgen\ContentBrowser\Registry\BackendRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function is_array;
use function is_scalar;

final class ContentBrowserType extends AbstractType
{
    private BackendRegistry $backendRegistry;

    public function __construct(BackendRegistry $backendRegistry)
    {
        $this->backendRegistry = $backendRegistry;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['item_type', 'start_location', 'custom_params']);

        $resolver->setAllowedTypes('item_type', 'string');
        $resolver->setAllowedTypes('start_location', ['int', 'string', 'null']);
        $resolver->setAllowedTypes('custom_params', 'array');

        $resolver->setAllowedValues(
            'item_type',
            fn (string $itemType): bool => $this->backendRegistry->hasBackend($itemType),
        );

        $resolver->setAllowedValues(
            'custom_params',
            static function (array $customParams): bool {
                foreach ($customParams as $customParam) {
                    if (!is_scalar($customParam) && !is_array($customParam)) {
                        return false;
                    }

                    if (is_array($customParam)) {
                        foreach ($customParam as $innerCustomParam) {
                            if (!is_scalar($innerCustomParam)) {
                                return false;
                            }
                        }
                    }
                }

                return true;
            },
        );

        $resolver->setDefault('start_location', null);
        $resolver->setDefault('custom_params', []);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $item = null;
        if ($form->getData() !== null) {
            try {
                $backend = $this->backendRegistry->getBackend($options['item_type']);
                $item = $backend->loadItem($form->getData());
            } catch (NotFoundException $e) {
                // Do nothing
            }
        }

        $view->vars['item'] = $item;
        $view->vars['item_type'] = $options['item_type'];
        $view->vars['start_location'] = $options['start_location'];
        $view->vars['custom_params'] = $options['custom_params'];
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ngcb';
    }
}
