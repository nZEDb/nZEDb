<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2019 nZEDb
 */
namespace App\Mailer;

use App\Model\Table\SettingsTable as Settings;
use App\Model\Entity\User;
use Cake\Mailer\Mailer;


class UserMailer extends Mailer
{
	/**
	 * Forgotten password mailer
	 *
	 * @param \App\Model\Entity\User $user
	 *
	 * @return void
	 */
	public function forgotten(User $user): void
	{
		$title = Settings::getValue('site.main.title');
		$this
			->setTo($user->email, $user->username)
			->setSubject($title . ' Forgotten Password Request')
			->setTemplate('password', 'default')
			->setFrom(Settings::getValue('site.main.email'), 'Admin@' . $title)
			->setTemplate('forgotten', 'password')
			->setEmailFormat('both');
	}

	/**
	 * New user Welcome mailer.
	 *
	 * @param \App\Model\Entity\User $user
	 *
	 * @return void
	 */
	public function welcome(User $user)
	{
		$this
			->setTo($user->email, $user->username)
			->setSubject(sprintf('Welcome %s', $user->name))
			// By default template with same name as method name is used.
			->setTemplate('welcome_mail', 'custom');
	}
}
