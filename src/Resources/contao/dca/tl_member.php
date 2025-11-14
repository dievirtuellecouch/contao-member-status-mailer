<?php

// Hide the regular Contao toggle (core eye) and prevent it from being added
unset($GLOBALS['TL_DCA']['tl_member']['list']['operations']['toggle']);
if (isset($GLOBALS['TL_DCA']['tl_member']['fields']['disable'])) {
    $GLOBALS['TL_DCA']['tl_member']['fields']['disable']['toggle'] = false;
    $GLOBALS['TL_DCA']['tl_member']['fields']['disable']['reverseToggle'] = false;
}

// Add list operation with custom toggle + mail
$GLOBALS['TL_DCA']['tl_member']['list']['operations']['msm'] = [
    'label' => ['Aktivieren/Deaktivieren (mit Mail)', ''],
    'href'  => 'key=msm',
    'icon'  => 'visible.svg',
    'button_callback' => [\Websailing\MemberStatusMailerBundle\Service\StatusHandler::class, 'button']
];
