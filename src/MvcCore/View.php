<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore;

/**
 * Core view:
 * - Static storage for
 *   - commonly used document type
 *   - common views extension
 *   - common directories names containing view scripts
 *   - common views helpers namespaces
 * - It's possible to use this class for any controller, sub controller or form.
 * - View pre render preparing and rendering.
 * - View helpers management on demand:
 *   - Creating by predefined class namespaces.
 *   - global static helpers instances storage and repeatable calling.
 * - Views sub scripts relative path solving in:
 *   `<?php $this->RenderScript('./any-subdirectory/script-to-render.php'); ?>`
 * - `Url()` - proxy method from `\MvcCore\Router` targeting to configured router.
 * - `AssetUrl()` - proxy method from `\MvcCore\Controller`.
 * - Magic calls:
 *   - __call() - To handler any view helper, if no helper found - exception thrown.
 *   - __set() - To set anything from controller to get it back in view.
 *   - __get() - To get anything in view previously initialized from controller.
 * - Optional direct code evaluation.
 * - No special view language implemented, use `short_open_tags` (`<?=...?>`) allowed by default.
 *
 * MvcCore view properties and helpers:
 * @property-read \MvcCore\Controller $controller Currently dispatched controller instance.
 * @method string Url($controllerActionOrRouteName = 'Index:Index', array $params = []) Generates url by `"Controller:Action"` name and params array or generate url by route name and params array.
 * @method string AssetUrl($path = '') Return asset path or single file mode URL for small assets handled by internal controller action `"Controller:Asset"`.
 * @method \MvcCore\Ext\Views\Helpers\Css Css(string $groupName = self::GROUP_NAME_DEFAULT) Get css helper instance by group name. To use this method, you need to install extension `mvccore/ext-view-helper-assets`.
 * @method \MvcCore\Ext\Views\Helpers\Js Js(string $groupName = self::GROUP_NAME_DEFAULT) Get js helper instance by group name. To use this method, you need to install extension `mvccore/ext-view-helper-assets`.
 * @method string FormatDate(\DateTime|\IntlCalendar|int $dateTimeOrTimestamp = NULL, int|string $dateTypeOrFormatMask = NULL, int $timeType = NULL, string|\IntlTimeZone|\DateTimeZone $timeZone = NULL, int $calendar = NULL) Format given date time by `Intl` extension or by `strftime()` as fallback. To use this method, you need to install extension `mvccore/ext-view-helper-formatdatetime`.
 * @method string FormatNumber(float|int $number = 0.0, int $decimals = 0, string $dec_point = NULL , string $thousands_sep = NULL) To use this method, you need to install extension `mvccore/ext-view-helper-formatnumber`.
 * @method string FormatMoney(float|int$number = 0.0, int $decimals = 0, string $dec_point = NULL , string $thousands_sep = NULL) To use this method, you need to install extension `mvccore/ext-view-helper-formatmoney`.
 * @method string LineBreaks(string $text, string $lang = '') Prevent breaking line inside numbers, after week words, shortcuts, numbers and units and much more, very configurable. To use this method, you need to install extension `mvccore/ext-view-helper-linebreaks`.
 * @method string DataUrl(string $relativeOrAbsolutePath) Return any file content by given relative or absolute path in data URL like `data:image/png;base64,iVBOR..`. Path could be relative from currently rendered view, relative from application root or absolute path to file. To use this method, you need to install extension `mvccore/ext-view-helper-dataurl`.
 * @method string WriteByJS(string $string) Return any given HTML code as code rendered in javascript: `<script>document.write(String.fromCharCode(...));</script>`. To use this method, you need to install extension `mvccore/ext-view-helper-writebyjs`.
 * @method string Truncate(string $text, int $maxChars = 200, bool $isHtml = NULL) Truncate plain text or text with html tags by given max. characters number and add three dots at the end. To use this method, you need to install extension `mvccore/ext-view-helper-truncate`.
 */
class View implements IView
{
	use \MvcCore\View\Props;
	use \MvcCore\View\GettersSetters;
	use \MvcCore\View\Rendering;
	use \MvcCore\View\DirectoryMethods;
	use \MvcCore\View\MagicMethods;
	use \MvcCore\View\UrlHelpers;
	use \MvcCore\View\ViewHelpers;
	use \MvcCore\View\Escaping;
	use \MvcCore\View\LocalMethods;
}
