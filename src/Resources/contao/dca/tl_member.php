<?php

// Add list operation with custom toggle + mail
$GLOBALS['TL_DCA']['tl_member']['list']['operations']['msm'] = [
    'label' => ['Aktivieren/Deaktivieren (mit Mail)', ''],
    'href'  => 'key=msm',
    'icon'  => 'visible.svg',
    'button_callback' => [\Websailing\MemberStatusMailerBundle\Service\StatusHandler::class, 'button']
];

