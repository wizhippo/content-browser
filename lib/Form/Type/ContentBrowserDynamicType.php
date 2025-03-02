<?php

declare(strict_types=1);

namespace Netgen\ContentBrowser\Form\Type;

use Netgen\ContentBrowser\Config\Configuration;
use Netgen\ContentBrowser\Exceptions\NotFoundException;
use Netgen\ContentBrowser\Registry\BackendRegistry;
use Netgen\ContentBrowser\Registry\ConfigRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function array_filter;
use function array_flip;
use function array_map;
use function count;
use function in_array;
use function is_array;
use function is_scalar;
use function trim;

final class ContentBrowserDynamicType extends AbstractType
{
    private BackendRegistry $backendRegistry;

    private ConfigRegistry $configRegistry;

    public function __construct(
        BackendRegistry $backendRegistry,
        ConfigRegistry $configRegistry
    ) {
        $this->backendRegistry = $backendRegistry;
        $this->configRegistry = $configRegistry;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['item_types', 'start_location', 'custom_params']);

        $resolver->setAllowedTypes('start_location', ['int', 'string', 'null']);
        $resolver->setAllowedTypes('custom_params', 'array');
        $resolver->setAllowedTypes('item_types', 'string[]');

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

        $resolver->setDefault('item_types', []);
        $resolver->setDefault('start_location', null);
        $resolver->setDefault('custom_params', []);

        $resolver->setNormalizer(
            'item_types',
            function (Options $options, array $itemTypes): array {
                $validItemTypes = [];

                foreach ($itemTypes as $itemType) {
                    if ($this->backendRegistry->hasBackend($itemType)) {
                        $validItemTypes[] = $itemType;
                    }
                }

                return $validItemTypes;
            },
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'item_type',
            ChoiceType::class,
            [
                'choices' => $this->getEnabledItemTypes($options['item_types']),
                'choice_translation_domain' => 'ngcb',
            ],
        );

        $builder->add('item_value', HiddenType::class);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $item = null;
        $itemValue = trim($form->get('item_value')->getData() ?? '');
        $itemType = trim($form->get('item_type')->getData() ?? '');

        if ($itemValue !== '' && $itemType !== '') {
            try {
                $backend = $this->backendRegistry->getBackend($itemType);
                $item = $backend->loadItem($itemValue);
            } catch (NotFoundException $e) {
                // Do nothing
            }
        }

        $view->vars['item'] = $item;
        $view->vars['start_location'] = $options['start_location'];
        $view->vars['custom_params'] = $options['custom_params'];
    }

    public function getBlockPrefix(): string
    {
        return 'ngcb_dynamic';
    }

    /**
     * Returns the enabled item types based on provided list.
     *
     * @param string[] $itemTypes
     *
     * @return string[]
     */
    private function getEnabledItemTypes(array $itemTypes): array
    {
        $allItemTypes = array_flip(
            array_map(
                static fn (Configuration $config): string => $config->getItemName(),
                $this->configRegistry->getConfigs(),
            ),
        );

        if (count($itemTypes) === 0) {
            return $allItemTypes;
        }

        return array_filter(
            $allItemTypes,
            static fn (string $itemType): bool => in_array($itemType, $itemTypes, true),
        );
    }
}
