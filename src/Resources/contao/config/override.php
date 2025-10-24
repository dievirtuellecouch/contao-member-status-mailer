<?php

// Override options_callback to ensure dropdowns are populated
$GLOBALS['TL_DCA']['tl_settings']['fields']['msm_nc_enabled_notification']['options_callback'] = [\Websailing\MemberStatusMailerBundle\Service\SettingsOptions::class, 'enabled'];
$GLOBALS['TL_DCA']['tl_settings']['fields']['msm_nc_disabled_notification']['options_callback'] = [\Websailing\MemberStatusMailerBundle\Service\SettingsOptions::class, 'disabled'];

