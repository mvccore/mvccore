<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Controller;

/**
 * @mixin \MvcCore\Controller
 */
trait FlashMessages {

	/**
	 * @inheritDoc
	 * @param  string            $msg
	 * Flash message text to display in next request(s).
	 * @param  int|list<int>     $options
	 * Could be defined as integer or as array with integer 
	 * keys and values. Use flags like 
	 * `\MvcCore\IController::FLASH_MESSAGE_*`.
	 * @param  array<int,string> $replacements
	 * Array with integer (`{0},{1},{2}...`) or 
	 * named (`{two},{two},{three}...`) replacements.
	 * @return \MvcCore\Controller Returns current controller context.
	 */
	public function FlashMessageAdd ($msg, $options = \MvcCore\IController::FLASH_MESSAGE_TYPE_DEFAULT, array $replacements = []) {
		$ctrl = \MvcCore\Application::GetInstance()->GetController();
		static::flashMessagesAddHandler($ctrl);
		$serializeFn = function_exists('igbinary_serialize') ? 'igbinary_serialize' : 'serialize';
		$messageImprint = md5(call_user_func($serializeFn, func_get_args()));
		
		// To extend this method (with translator for example), translate the message here:
		if (count($replacements) > 0) 
			foreach ($replacements as $replacementKey => $replacementValue) 
				$msg = str_replace('{'.$replacementKey.'}', $replacementValue, $msg);
		
		static::flashMessageAddOptions($ctrl, $messageImprint, $msg, $options);
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @return array<string,\stdClass>
	 */
	public function FlashMessagesGetClean () {
		$ctrl = $this->application->GetController();
		$session = static::flashMessagesGetSession($ctrl);
		$result = [];
		/** @var array<string,\stdClass> $store */
		$store = isset($session->store) ? $session->store : [];
		if (count($store) === 0) {
			$session->Destroy();
		} else {
			$flashMessagesToKeep = [];
			$reqStartDatetime = (new \DateTime)->setTimestamp(
				intval(round($this->request->GetStartTime()))
			);
			foreach ($store as $hash => $flashMessage) {
				$hoops = $flashMessage->hoops - 1;
				$expirationIsNull = $flashMessage->expiration === NULL;
				$expirationGone = $expirationIsNull
					? TRUE
					: $flashMessage->expiration < $reqStartDatetime;
				$hoopsGone = $hoops < 1;
				if ($expirationGone && $hoopsGone) {
					unset($store[$hash]);
				} else {
					if (!$hoopsGone) $flashMessage->hoops -= 1;
					$flashMessagesToKeep[$hash] = $flashMessage;
				}
				if ($expirationIsNull || !$expirationGone)
					$result[$hash] = $flashMessage;
			}
			$ctrl->flashMessages = $ctrl->flashMessages === NULL
				? $flashMessagesToKeep
				: array_merge($flashMessagesToKeep, $ctrl->flashMessages);
			$session->store = $store; /** @phpstan-ignore-line */
		}
		return $result;
	}
	
	/**
	 * Add post dispatch handler if necessary to store 
	 * flash messages into session at the request end.
	 * @internal
	 * @param  \MvcCore\Controller $mainCtrl Main MvcCore controller.
	 * @return void
	 */
	protected static function flashMessagesAddHandler (\MvcCore\IController $mainCtrl) {
		$mainCtrl = \MvcCore\Application::GetInstance()->GetController();
		if ($mainCtrl->flashMessages !== NULL) return;
		$mainCtrl->flashMessages = [];
		$mainCtrl->application->AddPostDispatchHandler(function () use ($mainCtrl) {
			$session = static::flashMessagesGetSession($mainCtrl);
			if (is_array($mainCtrl->flashMessages) && count($mainCtrl->flashMessages) > 0) { /** @phpstan-ignore-line */
				$session->store = $mainCtrl->flashMessages; /** @phpstan-ignore-line */
				$mainCtrl->flashMessages = NULL;
			} else {
				$session->Destroy();
			}
		});
	}

	/**
	 * Add translated flash message record with processed 
	 * replacements under given imprint and complete all options.
	 * @internal
	 * @param  \MvcCore\Controller $mainCtrl       Main MvcCore controller.
	 * @param  string              $messageImprint Message, options and replacements MD5 imprint.
	 * @param  string              $msg            Message string, optionally translated, processed with replacements.
	 * @param  int|list<int>       $options        Raw not processed options.
	 * @return void
	 */
	protected static function flashMessageAddOptions (\MvcCore\IController $mainCtrl, $messageImprint, $msg, $options) {
		$averageReadingSpeed = $mainCtrl::$flashMessagesReadingSpeedCfg['averageWordsPerMinute']; // words per minute
		$msgWordsCount = str_word_count($msg);
		$minTimeoutMs = $mainCtrl::$flashMessagesReadingSpeedCfg['minimalMessageTimeout'];
		$timeoutMs = intval(round(($msgWordsCount / $averageReadingSpeed) * 60.0 * 1000.0));
		$type = NULL;
		$closeable = FALSE;
		$autohide = FALSE;
		$expiration = NULL;
		$hoops = 1;
		$timeout = max($timeoutMs, $minTimeoutMs);
		if (is_int($options)) {
			foreach ($mainCtrl::$flashMessagesTypes as $typeFlag => $typeName) 
				if (($options & $typeFlag) !== 0 && $type = $typeName) 
					break;
			$autohide = ($options & $mainCtrl::FLASH_MESSAGE_AUTOHIDE) !== 0;
			$closeable = ($options & $mainCtrl::FLASH_MESSAGE_CLOSEABLE) !== 0;
		} else if (is_array($options)) {
			if (
				isset($options[$mainCtrl::FLASH_MESSAGE_TYPE]) && 
				isset($mainCtrl::$flashMessagesTypes[$options[$mainCtrl::FLASH_MESSAGE_TYPE]])
			) $type = $mainCtrl::$flashMessagesTypes[$options[$mainCtrl::FLASH_MESSAGE_TYPE]];
			if (isset($options[$mainCtrl::FLASH_MESSAGE_AUTOHIDE]))
				$autohide	= $options[$mainCtrl::FLASH_MESSAGE_AUTOHIDE];
			if (isset($options[$mainCtrl::FLASH_MESSAGE_TIMEOUT]))
				$timeout	= $options[$mainCtrl::FLASH_MESSAGE_TIMEOUT];
			if (isset($options[$mainCtrl::FLASH_MESSAGE_CLOSEABLE]))
				$closeable	= $options[$mainCtrl::FLASH_MESSAGE_CLOSEABLE];
			if (isset($options[$mainCtrl::FLASH_MESSAGE_EXPIRATION]))
				$expiration	= $options[$mainCtrl::FLASH_MESSAGE_EXPIRATION];
			if (isset($options[$mainCtrl::FLASH_MESSAGE_HOOPS]))
				$hoops		= $options[$mainCtrl::FLASH_MESSAGE_HOOPS];
		}
		$mainCtrl->flashMessages[$messageImprint] = (object) [
			'text'			=> $msg, 
			'type'			=> $type, 
			'autohide'		=> $autohide,
			'timeout'		=> $timeout,
			'closeable'		=> $closeable,
			'expiration'	=> $expiration,
			'hoops'			=> $hoops,
		];
	}

	/**
	 * Get raw flash messages session store.
	 * @internal
	 * @param  \MvcCore\Controller $mainCtrl Main MvcCore controller.
	 * @return \MvcCore\Session
	 */
	protected static function flashMessagesGetSession (\MvcCore\IController $mainCtrl) {
		$session = $mainCtrl->GetSessionNamespace($mainCtrl::FLASH_MESSAGES_SESSION_NAMESPACE)
			->SetExpirationHoops(1, \MvcCore\ISession::EXPIRATION_HOOPS_IGNORE_DEFAULT)
			->SetExpirationSeconds(0);
		if (!isset($session->store))
			$session->store = []; /** @phpstan-ignore-line */
		return $session;
	}
}
