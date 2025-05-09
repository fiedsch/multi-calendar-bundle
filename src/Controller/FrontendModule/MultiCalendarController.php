<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Fiedsch\MultiCalendarBundle\Controller\FrontendModule;

use Contao\BackendTemplate;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Twig\FragmentTemplate;
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

        // Variable namesa naloguous to Contao's CalendarBundle
        $template->prevHref = '?month=202401';
        $template->prevTitle = '2024';
        $template->prevLink = '2024';

        $template->current = '2025';

        $template->nextHref = '?month=202601';
        $template->nextTitle = '2026';
        $template->nextLink = '2026';

        return $template->getResponse();
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