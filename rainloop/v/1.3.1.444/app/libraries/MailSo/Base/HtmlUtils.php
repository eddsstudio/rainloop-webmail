<?php

namespace MailSo\Base;

/**
 * @category MailSo
 * @package Base
 */
class HtmlUtils
{
	/**
	 * @access private
	 */
	private function __construct()
	{
	}

	/**
	 * @param string $sText
	 *
	 * @return \DOMDocument | bool
	 */
	public static function GetDomFromText($sText)
	{
		static $bOnce = true;
		if ($bOnce)
		{
			$bOnce = false;
			if (\MailSo\Base\Utils::FunctionExistsAndEnabled('libxml_use_internal_errors'))
			{
				@\libxml_use_internal_errors(true);
			}
		}

		$oDom = new \DOMDocument('1.0', 'utf-8');

//		@$oDom->loadHTML('<'.'?xml version="1.0" encoding="utf-8"?'.'>'.
//			'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$sText.'</body></html>');
		@$oDom->loadHTML('<'.'?xml version="1.0" encoding="utf-8"?'.'>'.$sText);

		return $oDom;
	}

	/**
	 * @param string $sHtml
	 * @param bool $bSetupBody = false
	 *
	 * @return string
	 */
	public static function ClearBodyAndHtmlTag($sHtml, $bSetupBody = false)
	{
		$sHtml = \preg_replace('/<body([^>]*)>/im', '<div'.($bSetupBody ? ' class="mailso-body" ' : '').'\\1>', $sHtml);
		$sHtml = \preg_replace('/<\/body>/im', '</div>', $sHtml);
		$sHtml = \preg_replace('/<html([^>]*)>/im', '<div\\1>', $sHtml);
		$sHtml = \preg_replace('/<\/html>/im', '</div>', $sHtml);
		
		return $sHtml;
	}

	/**
	 * @param string $sHtml
	 *
	 * @return string
	 */
	public static function ClearTags($sHtml)
	{
		$aRemoveTags = array(
			'head', 'link', 'base', 'meta', 'title', 'style', 'script', 'bgsound',
			'object', 'embed', 'applet', 'mocha', 'iframe', 'frame', 'frameset'
		);

		$aToRemove = array(
			'/<!doctype[^>]*>/msi',
			'/<\?xml [^>]*\?>/msi'
		);

		foreach ($aRemoveTags as $sTag)
		{
			$aToRemove[] = '\'<'.$sTag.'[^>]*>.*?</[\s]*'.$sTag.'>\'msi';
			$aToRemove[] = '\'<'.$sTag.'[^>]*>\'msi';
			$aToRemove[] = '\'</[\s]*'.$sTag.'[^>]*>\'msi';
		}

		return \preg_replace($aToRemove, '', $sHtml);
	}

	/**
	 * @param string $sHtml
	 *
	 * @return string
	 */
	public static function ClearOn($sHtml)
	{
		$aToReplace = array(
			'/on(Blur)/si',
			'/on(Change)/si',
			'/on(Click)/si',
			'/on(DblClick)/si',
			'/on(Error)/si',
			'/on(Focus)/si',
			'/on(KeyDown)/si',
			'/on(KeyPress)/si',
			'/on(KeyUp)/si',
			'/on(Load)/si',
			'/on(MouseDown)/si',
			'/on(MouseEnter)/si',
			'/on(MouseLeave)/si',
			'/on(MouseMove)/si',
			'/on(MouseOut)/si',
			'/on(MouseOver)/si',
			'/on(MouseUp)/si',
			'/on(Move)/si',
			'/on(Resize)/si',
			'/on(ResizeEnd)/si',
			'/on(ResizeStart)/si',
			'/on(Scroll)/si',
			'/on(Select)/si',
			'/on(Submit)/si',
			'/on(Unload)/si'
		);

		return \preg_replace($aToReplace, 'оn\\1', $sHtml);
	}

	/**
	 *
	 * @param string $sStyle
	 * @param \DOMElement $oElement
	 * @param bool $bHasExternals
	 * @param array $aFoundCIDs
	 *
	 * @return string
	 */
	public static function ClearStyle($sStyle, $oElement, &$bHasExternals, &$aFoundCIDs)
	{
		$sStyle = \trim($sStyle);
		$aOutStyles = array();
		$aStyles = \explode(';', $sStyle);

		$aMatch = array();
		foreach ($aStyles as $sStyleItem)
		{
			$aStyleValue = \explode(':', $sStyleItem, 2);
			$sName = \trim(\strtolower($aStyleValue[0]));
			$sValue = isset($aStyleValue[1]) ? \trim($aStyleValue[1]) : '';

			if ('position' === $sName && 'fixed' === \strtolower($sValue))
			{
				$sValue = 'absolute';
			}

			if (0 === \strlen($sName) || 0 === \strlen($sValue))
			{
				continue;
			}

			$sStyleItem = $sName.': '.$sValue;
			$aStyleValue = array($sName, $sValue);

			/*if (\in_array($sName, array('position', 'left', 'right', 'top', 'bottom', 'behavior', 'cursor')))
			{
				// skip
			}
			else */if (\in_array($sName, array('behavior', 'cursor')) ||
				('display' === $sName && 'none' === \strtolower($sValue)) ||
				\preg_match('/expression/i', $sValue) ||
				('text-indent' === $sName && '-' === \substr(trim($sValue), 0, 1))
			)
			{
				// skip
			}
			else if (\in_array($sName, array('background-image', 'background', 'list-style-image', 'content'))
				&& \preg_match('/url[\s]?\(([^)]+)\)/im', $sValue, $aMatch) && !empty($aMatch[1]))
			{
				$sFullUrl = \trim($aMatch[0], '"\' ');
				$sUrl = \trim($aMatch[1], '"\' ');
				$sStyleValue = \trim(\preg_replace('/[\s]+/', ' ', \str_replace($sFullUrl, '', $sValue)));
				$sStyleItem = empty($sStyleValue) ? '' : $sName.': '.$sStyleValue;

				if ('cid:' === \strtolower(\substr($sUrl, 0, 4)))
				{
					if ($oElement)
					{
						$oElement->setAttribute('data-x-style-cid-name',
							'background' === $sName ? 'background-image' : $sName);

						$oElement->setAttribute('data-x-style-cid', \substr($sUrl, 4));

						$aFoundCIDs[] = \substr($sUrl, 4);
					}
				}
				else
				{
					if ($oElement)
					{
						if (\preg_match('/http[s]?:\/\//i', $sUrl))
						{
							$bHasExternals = true;
							if (\in_array($sName, array('background-image', 'list-style-image', 'content')))
							{
								$sStyleItem = '';
							}

							$sTemp = '';
							if ($oElement->hasAttribute('data-x-style-url'))
							{
								$sTemp = \trim($oElement->getAttribute('data-x-style-url'));
							}

							$sTemp = empty($sTemp) ? '' : (';' === \substr($sTemp, -1) ? $sTemp.' ' : $sTemp.'; ');

							$oElement->setAttribute('data-x-style-url', \trim($sTemp.
								('background' === $sName ? 'background-image' : $sName).': '.$sFullUrl, ' ;'));
						}
						else if ('data:image/' !== \strtolower(\substr(\trim($sUrl), 0, 11)))
						{
							$oElement->setAttribute('data-x-broken-style-src', $sFullUrl);
						}
					}
				}

				if (!empty($sStyleItem))
				{
					$aOutStyles[] = $sStyleItem;
				}
			}
			else if ('height' === $sName)
			{
//				$aOutStyles[] = 'min-'.ltrim($sStyleItem);
				$aOutStyles[] = $sStyleItem;
			}
			else
			{
				$aOutStyles[] = $sStyleItem;
			}
		}

		return \implode(';', $aOutStyles);
	}

	/**
	 * @param string $sHtml
	 * @param bool $bHasExternals
	 * @param array $aFoundCIDs
	 *
	 * @return string
	 */
	public static function ClearHtml($sHtml, &$bHasExternals = false, &$aFoundCIDs = array())
	{
		$sHtml = null === $sHtml ? '' : (string) $sHtml;
		$sHtml = \trim($sHtml);
		if (0 === \strlen($sHtml))
		{
			return '';
		}

		$bHasExternals = false;

		$sHtml = \MailSo\Base\HtmlUtils::ClearTags($sHtml);
		$sHtml = \MailSo\Base\HtmlUtils::ClearOn($sHtml);
//		$sHtml = \MailSo\Base\HtmlUtils::ClearBodyAndHtmlTag($sHtml);

		// Dom Part
		$oDom = \MailSo\Base\HtmlUtils::GetDomFromText($sHtml);
		unset($sHtml);

		if ($oDom)
		{
			$aNodes = $oDom->getElementsByTagName('*');
			foreach ($aNodes as /* @var $oElement \DOMElement */ $oElement)
			{
				$sTagNameLower = \strtolower($oElement->tagName);

				if ('iframe' === $sTagNameLower || 'frame' === $sTagNameLower)
				{
					$oElement->setAttribute('src', 'javascript:false');
				}

				if (\in_array($sTagNameLower, array('a', 'form', 'area')))
				{
					$oElement->setAttribute('target', '_blank');
				}

				if (\in_array($sTagNameLower, array('a', 'form', 'area', 'input', 'button', 'textarea')))
				{
					$oElement->setAttribute('tabindex', '-1');
				}

//				if ('blockquote' === $sTagNameLower)
//				{
//					$oElement->removeAttribute('style');
//				}

				@$oElement->removeAttribute('id');
				@$oElement->removeAttribute('class');
				@$oElement->removeAttribute('contenteditable');
				@$oElement->removeAttribute('designmode');
				@$oElement->removeAttribute('data-bind');

				if ($oElement->hasAttribute('src'))
				{
					$sSrc = \trim($oElement->getAttribute('src'));
					$oElement->removeAttribute('src');

					if ('cid:' === \strtolower(\substr($sSrc, 0, 4)))
					{
						$oElement->setAttribute('data-x-src-cid', \substr($sSrc, 4));
						$aFoundCIDs[] = \substr($sSrc, 4);
					}
					else
					{
						if (\preg_match('/http[s]?:\/\//i', $sSrc))
						{
							$oElement->setAttribute('data-x-src', $sSrc);
							$bHasExternals = true;
						}
						else if ('data:image/' === \strtolower(\substr(\trim($sSrc), 0, 11)))
						{
							$oElement->setAttribute('src', $sSrc);
						}
						else
						{
							$oElement->setAttribute('data-x-broken-src', $sSrc);
						}
					}
				}

				$sBackground = $oElement->hasAttribute('background')
					? \trim($oElement->getAttribute('background')) : '';
				$sBackgroundColor = $oElement->hasAttribute('bgcolor')
					? \trim($oElement->getAttribute('bgcolor')) : '';

				if (!empty($sBackground) || !empty($sBackgroundColor))
				{
					$aStyles = array();
					$sStyles = $oElement->hasAttribute('style')
						? $oElement->getAttribute('style') : '';

					if (!empty($sBackground))
					{
						$aStyles[] = 'background-image: url(\''.$sBackground.'\')';
						$oElement->removeAttribute('background');
					}

					if (!empty($sBackgroundColor))
					{
						$aStyles[] = 'background-color: '.$sBackgroundColor;
						$oElement->removeAttribute('bgcolor');
					}

					$oElement->setAttribute('style', (empty($sStyles) ? '' : $sStyles.'; ').\implode('; ', $aStyles));
				}

				if ($oElement->hasAttribute('style'))
				{
					$oElement->setAttribute('style',
						\MailSo\Base\HtmlUtils::ClearStyle($oElement->getAttribute('style'), $oElement, $bHasExternals, $aFoundCIDs));
				}
			}

			$sResult = $oDom->saveHTML();
		}

		unset($oDom);

		$sResult = \MailSo\Base\HtmlUtils::ClearTags($sResult);
		$sResult = \MailSo\Base\HtmlUtils::ClearBodyAndHtmlTag($sResult, true);

		return \trim($sResult);
	}

	/**
	 * @param string $sHtml
	 * @param array $aFoundCids = array()
	 * @param array|null $mFoundDataURL = null
	 *
	 * @return string
	 */
	public static function BuildHtml($sHtml, &$aFoundCids = array(), &$mFoundDataURL = null)
	{
		$oDom = \MailSo\Base\HtmlUtils::GetDomFromText($sHtml);
		unset($sHtml);

		$aNodes = $oDom->getElementsByTagName('*');
		foreach ($aNodes as /* @var $oElement \DOMElement */ $oElement)
		{
			$sTagNameLower = \strtolower($oElement->tagName);
			
			if ($oElement->hasAttribute('data-x-src-cid'))
			{
				$sCid = $oElement->getAttribute('data-x-src-cid');
				$oElement->removeAttribute('data-x-src-cid');

				if (!empty($sCid))
				{
					$aFoundCids[] = $sCid;

					@$oElement->removeAttribute('src');
					$oElement->setAttribute('src', 'cid:'.$sCid);
				}
			}

			if ($oElement->hasAttribute('data-x-broken-src'))
			{
				$oElement->setAttribute('src', $oElement->getAttribute('data-x-broken-src'));
				$oElement->removeAttribute('data-x-broken-src');
			}

			if ($oElement->hasAttribute('data-x-src'))
			{
				$oElement->setAttribute('src', $oElement->getAttribute('data-x-src'));
				$oElement->removeAttribute('data-x-src');
			}

			if ($oElement->hasAttribute('data-x-href'))
			{
				$oElement->setAttribute('href', $oElement->getAttribute('data-x-href'));
				$oElement->removeAttribute('data-x-href');
			}

			if ($oElement->hasAttribute('data-x-style-cid-name') && $oElement->hasAttribute('data-x-style-cid'))
			{
				$sCidName = $oElement->getAttribute('data-x-style-cid-name');
				$sCid = $oElement->getAttribute('data-x-style-cid');

				$oElement->removeAttribute('data-x-style-cid-name');
				$oElement->removeAttribute('data-x-style-cid');
				if (!empty($sCidName) && !empty($sCid) && \in_array($sCidName,
					array('background-image', 'background', 'list-style-image', 'content')))
				{
					$sStyles = '';
					if ($oElement->hasAttribute('style'))
					{
						$sStyles = \trim(\trim($oElement->getAttribute('style')), ';');
					}

					$sBack = $sCidName.': url(cid:'.$sCid.')';
					$sStyles = \preg_replace('/'.\preg_quote($sCidName, '/').':\s?[^;]+/i', $sBack, $sStyles);
					if (false === \strpos($sStyles, $sBack))
					{
						$sStyles .= empty($sStyles) ? '': '; ';
						$sStyles .= $sBack;
					}

					$oElement->setAttribute('style', $sStyles);
					$aFoundCids[] = $sCid;
				}
			}

			if ($oElement->hasAttribute('data-x-style-url'))
			{
				$sAddStyles = $oElement->getAttribute('data-x-style-url');
				$oElement->removeAttribute('data-x-style-url');

				if (!empty($sAddStyles))
				{
					$sStyles = '';
					if ($oElement->hasAttribute('style'))
					{
						$sStyles = \trim(\trim($oElement->getAttribute('style')), ';');
					}

					$oElement->setAttribute('style', (empty($sStyles) ? '' : $sStyles.'; ').$sAddStyles);
				}
			}

			if ('img' === $sTagNameLower && \is_array($mFoundDataURL))
			{
				$sSrc = $oElement->getAttribute('src');
				if ('data:image/' === \strtolower(\substr($sSrc, 0, 11)))
				{
					$sHash = \md5($sSrc);
					$mFoundDataURL[$sHash] = $sSrc;

					$oElement->setAttribute('src', 'cid:'.$sHash);
				}
			}
		}

		$sResult = $oDom->saveHTML();
		unset($oDom);

		$sResult = \MailSo\Base\HtmlUtils::ClearTags($sResult);
		$sResult = \MailSo\Base\HtmlUtils::ClearBodyAndHtmlTag($sResult);

		return '<!DOCTYPE html><html'.(\MailSo\Base\Utils::IsRTL($sResult) ? ' dir="rtl"' : ' dir="ltr"').'><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><head>'.
			'<body>'.\trim($sResult).'</body></html>';
	}

	/**
	 * @param string $sText
	 * @param bool $bLinksWithTargetBlank = true
	 *
	 * @return string
	 */
	public static function ConvertPlainToHtml($sText, $bLinksWithTargetBlank = true)
	{
		$sText = \trim($sText);
		if (0 === \strlen($sText))
		{
			return '';
		}

		$sText = \MailSo\Base\LinkFinder::NewInstance()
			->Text($sText)
			->UseDefaultWrappers($bLinksWithTargetBlank)
//			->CompileText(true, false);
			->CompileText(true, true);

		$sText = \str_replace("\r", '', $sText);

		$aText = \explode("\n", $sText);
		unset($sText);

		$bIn = false;
		$bDo = true;
		do
		{
			$bDo = false;
			$aNextText = array();
			foreach ($aText as $sTextLine)
			{
				$bStart = 0 === \strpos(\ltrim($sTextLine), '&gt;');
				if ($bStart && !$bIn)
				{
					$bDo = true;
					$bIn = true;
					$aNextText[] = '<blockquote>';
					$aNextText[] = \substr(\ltrim($sTextLine), 4);
				}
				else if (!$bStart && $bIn)
				{
					$bIn = false;
					$aNextText[] = '</blockquote>';
					$aNextText[] = $sTextLine;
				}
				else if ($bStart && $bIn)
				{
					$aNextText[] = \substr(\ltrim($sTextLine), 4);
				}
				else
				{
					$aNextText[] = $sTextLine;
				}
			}

			if ($bIn)
			{
				$bIn = false;
				$aNextText[] = '</blockquote>';
			}

			$aText = $aNextText;
		}
		while ($bDo);

		$sText = \join("\n", $aText);
		unset($aText);

		$sText = \preg_replace('/[\n][ ]+/', "\n", $sText);
//		$sText = \preg_replace('/[\s]+([\s])/', '\\1', $sText);

		$sText = \preg_replace('/<blockquote>[\s]+/i', '<blockquote>', $sText);
		$sText = \preg_replace('/[\s]+<\/blockquote>/i', '</blockquote>', $sText);

		$sText = \preg_replace('/<\/blockquote>([\n]{0,2})<blockquote>/i', '\\1', $sText);
		$sText = \preg_replace('/[\n]{3,}/', "\n\n", $sText);

		$sText = \strtr($sText, array(
			"\n" => "<br />",
			"\t" => '&nbsp;&nbsp;&nbsp;',
			'  ' => '&nbsp;&nbsp;'
		));

		return $sText;
	}
	
	/**
	 * @param string $sText
	 * 
	 * @return string
	 */
	public static function ConvertHtmlToPlain($sText)
	{
		$sText = trim(stripslashes($sText));
		$sText = preg_replace('/[\s]+/', ' ', $sText);
		$sText = preg_replace(array(
				"/\r/",
				"/[\n\t]+/",
				'/<script[^>]*>.*?<\/script>/i',
				'/<style[^>]*>.*?<\/style>/i',
				'/<title[^>]*>.*?<\/title>/i',
				'/<h[123][^>]*>(.+?)<\/h[123]>/i',
				'/<h[456][^>]*>(.+?)<\/h[456]>/i',
				'/<p[^>]*>/i',
				'/<br[^>]*>/i',
				'/<b[^>]*>(.+?)<\/b>/i',
				'/<i[^>]*>(.+?)<\/i>/i',
				'/(<ul[^>]*>|<\/ul>)/i',
				'/(<ol[^>]*>|<\/ol>)/i',
				'/<li[^>]*>/i',
				'/<a[^>]*href="([^"]+)"[^>]*>(.+?)<\/a>/i',
				'/<hr[^>]*>/i',
				'/(<table[^>]*>|<\/table>)/i',
				'/(<tr[^>]*>|<\/tr>)/i',
				'/<td[^>]*>(.+?)<\/td>/i',
				'/<th[^>]*>(.+?)<\/th>/i',
				'/&nbsp;/i',
				'/&quot;/i',
				'/&gt;/i',
				'/&lt;/i',
				'/&amp;/i',
				'/&copy;/i',
				'/&trade;/i',
				'/&#8220;/',
				'/&#8221;/',
				'/&#8211;/',
				'/&#8217;/',
				'/&#38;/',
				'/&#169;/',
				'/&#8482;/',
				'/&#151;/',
				'/&#147;/',
				'/&#148;/',
				'/&#149;/',
				'/&reg;/i',
				'/&bull;/i',
				'/&[&;]+;/i',
				'/&#39;/',
				'/&#160;/'
			), array(
				'',
				' ',
				'',
				'',
				'',
				"\n\n\\1\n\n",
				"\n\n\\1\n\n",
				"\n\n\t",
				"\n",
				'\\1',
				'\\1',
				"\n\n",
				"\n\n",
				"\n\t* ",
				'\\2 (\\1)',
				"\n------------------------------------\n",
				"\n",
				"\n",
				"\t\\1\n",
				"\t\\1\n",
				' ',
				'"',
				'>',
				'<',
				'&',
				'(c)',
				'(tm)',
				'"',
				'"',
				'-',
				"'",
				'&',
				'(c)',
				'(tm)',
				'--',
				'"',
				'"',
				'*',
				'(R)',
				'*',
				'',
				'\'',
				''
			), $sText);
		
		$sText = str_ireplace('<div>',"\n<div>", $sText);
		$sText = strip_tags($sText, '');
		$sText = preg_replace("/\n\\s+\n/", "\n", $sText);
		$sText = preg_replace("/[\n]{3,}/", "\n\n", $sText);

		return trim($sText);
	}
}
