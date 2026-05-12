<?php

namespace Websailing\MemberStatusMailerBundle\Service;

use Contao\Backend;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\MemberModel;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Contao\Email;
use Symfony\Component\Security\Csrf\CsrfToken;

class StatusHandler extends Backend
{
    public function button(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        $isDisabled = (int)($row['disable'] ?? 0) === 1;
        $label = $isDisabled ? ($GLOBALS['TL_LANG']['tl_member']['msm_inactive'][0] ?? 'Aktivieren') : ($GLOBALS['TL_LANG']['tl_member']['msm_active'][0] ?? 'Deaktivieren');
        $icon = $isDisabled ? 'invisible.svg' : 'visible.svg';
        $url = $this->addToUrl($href.'&id='.$row['id']);
        return '<a href="'.$url.'" title="'.StringUtil::specialchars($label).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a>';
    }

    public function handle(): void
    {
        $id = (int) Input::get('id');

        if ($id <= 0 || !$this->isValidRequestToken()) {
            Message::addError('Ungültige Anfrage.');
            Controller::redirect('contao?do=member');
        }

        $member = Database::getInstance()->prepare('SELECT id,disable FROM tl_member WHERE id=?')->limit(1)->execute($id);
        if (!$member->numRows) {
            Message::addError('Mitglied nicht gefunden.');
            Controller::redirect('contao?do=member');
        }

        $isCurrentlyDisabled = ((string) $member->disable === '1');
        $newVal = $isCurrentlyDisabled ? '' : '1';

        Database::getInstance()->prepare('UPDATE tl_member %s WHERE id=?')->set(['disable' => $newVal])->execute($id);
        $this->sendNotification($id, $isCurrentlyDisabled);
        Controller::redirect('contao?do=member');
    }

    private function sendNotification(int $id, bool $enabled): void
    {
        $member = MemberModel::findByPk($id);
        if (!$member) { return; }
        // Read notification config from settings
        $enabledId = (int) (\Contao\Config::get('msm_nc_enabled_notification') ?: 0);
        $disabledId = (int) (\Contao\Config::get('msm_nc_disabled_notification') ?: 0);
        $notificationId = $enabled ? $enabledId : $disabledId;
        if ($notificationId <= 0) { return; }

        try {
            /** @var \Terminal42\NotificationCenterBundle\NotificationCenter $nc */
            $nc = \Contao\System::getContainer()->get(\Terminal42\NotificationCenterBundle\NotificationCenter::class);
            $tokens = [
                'domain' => Environment::get('host'),
                'status' => $enabled ? 'enabled' : 'disabled',
                'member' => $member->row(),
                'admin_email' => ($GLOBALS['TL_ADMIN_EMAIL'] ?? ('noreply@'.Environment::get('host'))),
            ];
            $nc->sendNotification($notificationId, $tokens);
        } catch (\Throwable $e) {
            Message::addError($e->getMessage());
            try {
                $container = System::getContainer();
                $logsDir = (string) ($container->hasParameter('kernel.logs_dir') ? $container->getParameter('kernel.logs_dir') : ((string) $container->getParameter('kernel.project_dir')).'/var/logs');
                @file_put_contents($logsDir.'/member-status-mailer.log', date('c')." ERROR ".$e->getMessage()."\n", FILE_APPEND);
            } catch (\Throwable $e2) {}
        }
    }

    private function isValidRequestToken(): bool
    {
        try {
            $container = System::getContainer();
            $token = (string) (Input::get('rt') ?? '');

            return $token !== ''
                && $container->get('contao.csrf.token_manager')->isTokenValid(
                    new CsrfToken((string) $container->getParameter('contao.csrf_token_name'), $token)
                );
        } catch (\Throwable $e) {
            return false;
        }
    }
}
