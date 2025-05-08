<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Palette and fields: same as Contao's calendar module
$GLOBALS['TL_DCA']['tl_module']['palettes']['multi_calendar'] = $GLOBALS['TL_DCA']['tl_module']['palettes']['calendar'];
$GLOBALS['TL_DCA']['tl_module']['fields']['multi_calendar'] = $GLOBALS['TL_DCA']['tl_module']['fields']['cal_calendar'];

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][]  = 'mc_type';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['mc_type_custom'] = 'mc_back,mc_forward';

// add the module's special fields
PaletteManipulator::create()
    ->addLegend('mc_legend', 'config_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('mc_type', 'mc_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('multi_calendar', 'tl_module');
;

$GLOBALS['TL_DCA']['tl_module']['fields']['mc_type'] = array
(
    'inputType'               => 'select',
    'reference'               => &$GLOBALS['TL_LANG']['MC_TYPE'],
    'options'                 => ['year','custom'],
    'eval'                    => ['tl_class'=>'w50', 'submitOnChange'=>true],
    'sql'                     => ['type' => 'string', 'length' => 32, 'fixed' => true, 'default' => 'Jahr']
);

$GLOBALS['TL_DCA']['tl_module']['fields']['mc_back'] = array
(
    'inputType'               => 'text',
    'eval'                    => ['tl_class'=>'clr w50', 'rgxp' => 'natural'],
    'sql'                     => ['type' => 'integer', 'notnull' => false, 'unsigned' => true]
);

$GLOBALS['TL_DCA']['tl_module']['fields']['mc_forward'] = array
(
    'inputType'               => 'text',
    'eval'                    => ['tl_class'=>'w50', 'rgxp' => 'natural'],
    'sql'                     => ['type' => 'integer', 'notnull' => false, 'unsigned' => true]
);
