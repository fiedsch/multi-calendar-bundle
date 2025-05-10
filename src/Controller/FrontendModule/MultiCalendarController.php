<?php

declare(strict_types=1);

namespace Fiedsch\MultiCalendarBundle\Controller\FrontendModule;

use Contao\BackendTemplate;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Environment;
use Contao\Input;
use Contao\ModuleCalendar;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use RuntimeException;

#[AsFrontendModule(type: MultiCalendarController::TYPE, category: 'events')]
class MultiCalendarController extends AbstractFrontendModuleController
{
    const string TYPE = 'multi_calendar';

    public function __construct(
        private readonly ScopeMatcher $scopeMatcher
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            $backendTemplate = new BackendTemplate('be_wildcard');
            /** @noinspection PhpUndefinedFieldInspection */
            $backendTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['multi_calendar'][0] . ' ###';

            return new Response($backendTemplate->parse());
        }

        // Restrict to supported cases
        $model->cal_format = "cal_month";

        $requestParameterMonth = Input::get('month');
        if (null !== $requestParameterMonth && !preg_match('/^\d{6}$/', $requestParameterMonth)) {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }
        if (null === $requestParameterMonth) {
            $requestParameterMonth = sprintf('%s%s', date("Y"), date("m"));
        }

        $template->rendered_calendars = match ($model->mc_type) {
            'year' =>  $this->getYearCalendars($model, $requestParameterMonth),
            'custom' => $this->getCustomCalendars($model, $requestParameterMonth),
            'default' => throw new RuntimeException(sprintf('Unknown type %s', $model->my_type))
        };

        // Variable names analogue to Contao's CalendarBundle
        //$baseUri = $request->getScriptName();
        $baseUri = $request->getPathInfo();

        switch ($model->mc_type) {
            case 'year':
                $this->setYearTemplateVariables($template, $requestParameterMonth, $baseUri);
                break;
            case 'custom':
                $this->setCustomTemplateVariables($template, $requestParameterMonth, $baseUri);
                break;
            default:
                throw new RuntimeException(sprintf('Unknown type %s', $model->my_type));
        }

        return $template->getResponse();
    }

    protected function setYearTemplateVariables(FragmentTemplate $template, string $requestParameterMonth, string $baseUri): void
    {
                $currentYear = substr($requestParameterMonth, 0, 4);
                $prevYear = sprintf('%04d', (int)$currentYear - 1);
                $nextYear = sprintf('%04d', (int)$currentYear + 1);
                $template->prevHref = sprintf('%s?month=%s%s', $baseUri, $prevYear, '01');
                $template->prevTitle = $prevYear;
                $template->prevLink = $prevYear;

                $template->current = substr($requestParameterMonth, 0, 4);

                $template->nextHref = sprintf('%s?month=%s%s', $baseUri, $nextYear, '01');
                $template->nextTitle = $nextYear;
                $template->nextLink = $nextYear;
    }

    protected function setCustomTemplateVariables(FragmentTemplate $template, string $requestParameterMonth, string $baseUri): void
    {
                $currentYear = substr($requestParameterMonth, 0, 4);
                $currentMonth = substr($requestParameterMonth, 4, 2);
                $prevYear = sprintf('%04d', $currentMonth === '01' ? (int)$currentYear - 1 : $currentYear);
                $prevMonth = sprintf('%02d', $currentMonth === '01' ? '12' : $currentMonth - 1);
                $nextYear = sprintf('%04d', $currentMonth === '12' ? (int)$currentYear + 1 : $currentYear);
                $nextMonth = sprintf('%02d', $currentMonth === '12' ? '01' : $currentMonth + 1);

                $template->prevHref = sprintf('%s?month=%s%s', $baseUri, $prevYear, $prevMonth);
                $template->prevTitle = sprintf('%s %s', $GLOBALS['TL_LANG']['MONTHS'][$prevMonth-1], $prevYear);
                $template->prevLink = sprintf('%s %s', $GLOBALS['TL_LANG']['MONTHS'][$prevMonth-1], $prevYear);

                $template->current = sprintf('%s %s', $GLOBALS['TL_LANG']['MONTHS'][$currentMonth-1], $currentYear);

                $template->nextHref = sprintf('%s?month=%s%s', $baseUri, $nextYear, $nextMonth);
                $template->nextTitle = sprintf('%s %s', $GLOBALS['TL_LANG']['MONTHS'][$nextMonth-1], $nextYear);
                $template->nextLink = sprintf('%s %s', $GLOBALS['TL_LANG']['MONTHS'][$nextMonth-1], $nextYear);
    }

    protected function getYearCalendars(ModuleModel $model, string $requestParameterMonth): array
    {
        $displayYear = substr($requestParameterMonth, 0, 4);

        $calendar = new ModuleCalendar($model);
        $renderedCalendars = [];

        for ($month = 1; $month <= 12; ++$month) {
            try {
                $dateParameter = sprintf('%s%02d', $displayYear, $month);
                Input::setGet('month', $dateParameter);
                $renderedCalendars[] = $calendar->generate();
            } catch (Exception) {
                // ignore 404 page not found which gets generated when there are no events to display in a requested calendar
                $renderedCalendars[] = $this->getDummyHtmlForEmptyCalendar($dateParameter);
            }
        }

        return $renderedCalendars;
    }

    protected function getCustomCalendars(ModuleModel $model, string $requestParameterMonth): array
    {
        $displayYear = substr($requestParameterMonth, 0, 4);
        $displayMonth = substr($requestParameterMonth, 4, 2);

        $calendar = new ModuleCalendar($model);
        $renderedCalendars = [];

        $timeStampForFirstDayOfRequestedMonth = strtotime(sprintf('%s01', $requestParameterMonth));

        for ($back = $model->mc_back; $back > 0; --$back) {
            try {
                // $requestParameterMonth - x months
                $dateParameter = date("Ym", strtotime("-". $back." month", $timeStampForFirstDayOfRequestedMonth));
                Input::setGet('month', $dateParameter);
                $renderedCalendars[] = $calendar->generate();
            } catch (Exception) {
                // ignore 404 page not found which gets generated when there are no events to display in a requested calendar
                $renderedCalendars[] = $this->getDummyHtmlForEmptyCalendar($dateParameter);
            }
        }

        $dateParameter = sprintf('%s%s', $displayYear, $displayMonth);
        Input::setGet('month', $dateParameter);
        $renderedCalendars[] = $calendar->generate();

        for ($forward = 1; $forward <= $model->mc_forward; ++$forward) {
            try {
                // TODO: $displayYear$displayMont + x months
                $dateParameter = date("Ym", strtotime("+". $forward." month", $timeStampForFirstDayOfRequestedMonth));
                Input::setGet('month', $dateParameter);
                $renderedCalendars[] = $calendar->generate();
            } catch (Exception) {
                // ignore 404 page not found which gets generated when there are no events to display in a requested calendar
                $renderedCalendars[] = $this->getDummyHtmlForEmptyCalendar($dateParameter);
            }
        }

        return $renderedCalendars;
    }

    protected function getDummyHtmlForEmptyCalendar(string $ym): string
    {
        $year = substr($ym, 0, 4);
        $month = substr($ym, 4, 2);

        $monthFormatted = sprintf('%s %s', $GLOBALS['TL_LANG']['MONTHS'][(int)$month -1] ?? $month, $year);

        return $this->render('@FiedschMultiCalendar/mc_empty.html.twig', ['monthFormatted' => $monthFormatted])->getContent();

    }

}