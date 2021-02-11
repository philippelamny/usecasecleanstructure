<?php

namespace Vietanywhere\UseCase\CreateCleanStructure\Templates;

/**
 * Class TemplateBuilder
 * @package Vietanywhere\UseCase\CreateCleanStructure\Templates
 */
class TemplateBuilder
{

    public const __USE_CASE_MODEL__ = [
        'prefix' => 'Model',
        'file' => 'UseCaseModel'
    ];

    public const __USE_CASE_PRESENTATION_PRESENTER_VIEW_MODEL__ = [
        'prefix' => 'ViewModel',
        'file' => 'UseCasePresentationPresenterViewModel'
    ];

    public const __USE_CASE_PRESENTATION_PRENSENTER_VLEW_MODEL_FACTORY__ = [
        'prefix' => 'ViewModelFactory',
        'file' => 'UseCasePresentationPresenterViewModelFactory'
    ];

    public const __USE_CASE_PRESENTATION_PRESENTER_CONTRAT__ = [
        'prefix' => 'PresenterContract',
        'file' => 'UseCasePresentationPresenterContrat'
    ];

    public const __USE_CASE_PRESENTATION_VUE_PRESENTER__ = [
        'prefix' => 'VuePresenter',
        'file' => 'UseCasePresentationVuePresenter'
    ];

    public const __USE_CASE_PRESENTATION_VUE_VIEW_MODEL__ = [
        'prefix' => 'VueViewModel',
        'file' => 'UseCasePresentationVueViewModel'
    ];

    public const __USE_CASE__ = [
        'prefix' => '',
        'file' => 'UseCase'
    ];

    public const __USE_CASE_INPUT__ = [
        'prefix' => 'Input',
        'file' => 'UseCaseInput'
    ];

    public const __USE_CASE_OUTPUT__ = [
        'prefix' => 'Output',
        'file' => 'UseCaseOutput'
    ];

    /**
     * @param array $template
     * @param string $useCase
     * @return array|null
     */
    public static function buildTemplateClass(array $template, string $useCase) : ?array
    {
        if (array_key_exists('prefix', $template) && array_key_exists('file', $template)) {
            $path = __DIR__ . '/views/' . $template['file'];

            if (file_exists($path)) {
                return [
                    'content' => file_get_contents($path),
                    'className' => $useCase . $template['prefix']
                ];
            }
        }

        return null;
    }
}