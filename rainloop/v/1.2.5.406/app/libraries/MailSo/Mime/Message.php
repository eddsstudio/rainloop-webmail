<?php

namespace MailSo\Mime;

/**
 * @category MailSo
 * @package Mime
 */
class Message
{
	/**
	 * @var array
	 */
	private $aHeadersValue;

	/**
	 * @var array
	 */
	private $aAlternativeParts;

	/**
	 * @var \MailSo\Mime\AttachmentCollection
	 */
	private $oAttachmentCollection;

	/**
	 * @var string
	 */
	private $sMessageId;

	/**
	 * @var string
	 */
	private $sCustomXMailer;

	/**
	 * @access private
	 */
	private function __construct()
	{
		$this->aHeadersValue = array();
		$this->aAlternativeParts = array();
		$this->oAttachmentCollection = AttachmentCollection::NewInstance();
		$this->sMessageId = '';
		$this->sCustomXMailer = '';
		$this->bAddEmptyTextPart = true;
	}

	/**
	 * @return \MailSo\Mime\Message
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @return void
	 */
	public function DoesNotCreateEmptyTextPart()
	{
		$this->bAddEmptyTextPart = false;
	}

	/**
	 * @return string
	 */
	public function MessageId()
	{
		return $this->sMessageId;
	}

	/**
	 * @param string $sMessageId
	 *
	 * @return void
	 */
	public function SetMessageId($sMessageId)
	{
		$this->sMessageId = $sMessageId;
	}

	/**
	 * @param string $sHostName = ''
	 *
	 * @return void
	 */
	public function RegenerateMessageId($sHostName = '')
	{
		$this->SetMessageId($this->generateNewMessageId($sHostName));
	}

	/**
	 * @return \MailSo\Mime\AttachmentCollection
	 */
	public function Attachments()
	{
		return $this->oAttachmentCollection;
	}

	/**
	 * @return \MailSo\Mime\EmailCollection
	 */
	public function GetTo()
	{
		$oResult = \MailSo\Mime\EmailCollection::NewInstance();

		if (isset($this->aHeadersValue[\MailSo\Mime\Enumerations\Header::TO_]) &&
			$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::TO_] instanceof \MailSo\Mime\EmailCollection)
		{
			$oResult->MergeWithOtherCollection($this->aHeadersValue[\MailSo\Mime\Enumerations\Header::TO_]);
		}

		return $oResult->Unique();
	}

	/**
	 * @return \MailSo\Mime\EmailCollection
	 */
	public function GetRcpt()
	{
		$oResult = \MailSo\Mime\EmailCollection::NewInstance();

		if (isset($this->aHeadersValue[\MailSo\Mime\Enumerations\Header::TO_]) &&
			$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::TO_] instanceof \MailSo\Mime\EmailCollection)
		{
			$oResult->MergeWithOtherCollection($this->aHeadersValue[\MailSo\Mime\Enumerations\Header::TO_]);
		}

		if (isset($this->aHeadersValue[\MailSo\Mime\Enumerations\Header::CC]) &&
			$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::CC] instanceof \MailSo\Mime\EmailCollection)
		{
			$oResult->MergeWithOtherCollection($this->aHeadersValue[\MailSo\Mime\Enumerations\Header::CC]);
		}

		if (isset($this->aHeadersValue[\MailSo\Mime\Enumerations\Header::BCC]) &&
			$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::BCC] instanceof \MailSo\Mime\EmailCollection)
		{
			$oResult->MergeWithOtherCollection($this->aHeadersValue[\MailSo\Mime\Enumerations\Header::BCC]);
		}

		return $oResult->Unique();
	}

	/**
	 * @param string $sHeader
	 * @param string $sValue
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetCustomHeader($sHeaderName, $sValue)
	{
		$sHeaderName = \trim($sHeaderName);
		if (0 < \strlen($sHeaderName))
		{
			$this->aHeadersValue[$sHeaderName] = $sValue;
		}

		return $this;
	}

	/**
	 * @param string $sSubject
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetSubject($sSubject)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::SUBJECT] = $sSubject;

		return $this;
	}

	/**
	 * @param string $sInReplyTo
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetInReplyTo($sInReplyTo)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::IN_REPLY_TO] = $sInReplyTo;

		return $this;
	}

	/**
	 * @param string $sReferences
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetReferences($sReferences)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::REFERENCES] = $sReferences;

		return $this;
	}

	/**
	 * @param string $sEmail
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetReadConfirmation($sEmail)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::DISPOSITION_NOTIFICATION_TO] = $sEmail;
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::X_CONFIRM_READING_TO] = $sEmail;

		return $this;
	}

	/**
	 * @param int $iValue
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetPriority($iValue)
	{
		$sResult = '';
		switch ($iValue)
		{
			case \MailSo\Mime\Enumerations\MessagePriority::HIGH:
				$sResult = \MailSo\Mime\Enumerations\MessagePriority::HIGH.' (Highest)';
				break;
			case \MailSo\Mime\Enumerations\MessagePriority::NORMAL:
				$sResult = \MailSo\Mime\Enumerations\MessagePriority::NORMAL.' (Normal)';
				break;
			case \MailSo\Mime\Enumerations\MessagePriority::LOW:
				$sResult = \MailSo\Mime\Enumerations\MessagePriority::LOW.' (Lowest)';
				break;
		}

		if (0 < \strlen($sResult))
		{
			$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::X_PRIORITY] = $sResult;
		}

		return $this;
	}

	/**
	 * @param int $iValue
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetSensivity($iValue)
	{
		$sResult = '';
		switch ($iValue)
		{
			case \MailSo\Mime\Enumerations\Sensitivity::CONFIDENTIAL:
				$sResult = 'Company-Confidential';
				break;
			case \MailSo\Mime\Enumerations\Sensitivity::PERSONAL:
				$sResult = 'Personal';
				break;
			case \MailSo\Mime\Enumerations\Sensitivity::PRIVATE_:
				$sResult = 'Private';
				break;
		}

		if (0 < \strlen($sResult))
		{
			$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::SENSITIVITY] = $sResult;
		}

		return $this;
	}

	/**
	 * @param string $sXMailer
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetXMailer($sXMailer)
	{
		$this->sCustomXMailer = $sXMailer;

		return $this;
	}

	/**
	 * @param \MailSo\Mime\Email $oEmail
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetFrom(\MailSo\Mime\Email $oEmail)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::FROM_] = $oEmail;

		return $this;
	}

	/**
	 * @param \MailSo\Mime\EmailCollection $oEmails
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetTo(\MailSo\Mime\EmailCollection $oEmails)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::TO_] = $oEmails;

		return $this;
	}
	
	/**
	 * @param \MailSo\Mime\EmailCollection $oEmails
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetReplyTo(\MailSo\Mime\EmailCollection $oEmails)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::REPLY_TO] = $oEmails;

		return $this;
	}

	/**
	 * @param \MailSo\Mime\EmailCollection $oEmails
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetCc(\MailSo\Mime\EmailCollection $oEmails)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::CC] = $oEmails;

		return $this;
	}

	/**
	 * @param \MailSo\Mime\EmailCollection $oEmails
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetBcc(\MailSo\Mime\EmailCollection $oEmails)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::BCC] = $oEmails;

		return $this;
	}

	/**
	 * @param \MailSo\Mime\EmailCollection $oEmails
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetSender(\MailSo\Mime\EmailCollection $oEmails)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::SENDER] = $oEmails;

		return $this;
	}

	/**
	 * @param string $sType
	 * @param string $sUid
	 * @param string $sFolder
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function SetDraftInfo($sType, $sUid, $sFolder)
	{
		$this->aHeadersValue[\MailSo\Mime\Enumerations\Header::X_DRAFT_INFO] = \MailSo\Mime\ParameterCollection::NewInstance()
			->Add(\MailSo\Mime\Parameter::NewInstance('type', $sType))
			->Add(\MailSo\Mime\Parameter::NewInstance('uid', $sUid))
			->Add(\MailSo\Mime\Parameter::NewInstance('folder', base64_encode($sFolder)))
		;

		return $this;
	}

	/**
	 * @param string $sPlain
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function AddPlain($sPlain)
	{
		return $this->AddAlternative(
			\MailSo\Mime\Enumerations\MimeType::TEXT_PLAIN, trim($sPlain),
			\MailSo\Base\Enumerations\Encoding::QUOTED_PRINTABLE);
	}
	/**
	 * @param string $sHtml
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function AddHtml($sHtml)
	{
		return $this->AddAlternative(
			\MailSo\Mime\Enumerations\MimeType::TEXT_HTML, trim($sHtml),
			\MailSo\Base\Enumerations\Encoding::QUOTED_PRINTABLE);
	}

	/**
	 * @param string $sHtmlOrPlainText
	 * @param bool $bIsHtml = false
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function AddText($sHtmlOrPlainText, $bIsHtml = false)
	{
		return $bIsHtml ? $this->AddHtml($sHtmlOrPlainText) : $this->AddPlain($sHtmlOrPlainText);
	}

	/**
	 * @param string $sContentType
	 * @param string | resource $mData
	 * @param string $sContentTransferEncoding = ''
	 *
	 * @return \MailSo\Mime\Message
	 */
	public function AddAlternative($sContentType, $mData, $sContentTransferEncoding = '')
	{
		$this->aAlternativeParts[] = array($sContentType, $mData, $sContentTransferEncoding);

		return $this;
	}

	/**
	 * @return string
	 */
	private function generateNewBoundary()
	{
		return '----=_Part_'.mt_rand(100, 999).'_'.mt_rand(100000000, 999999999).'.'.time();
	}

	/**
	 * @param string $sHostName = ''
	 *
	 * @return string
	 */
	private function generateNewMessageId($sHostName = '')
	{
		if (0 === strlen($sHostName))
		{
			$sHostName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'mailso';
		}

		return '<'.md5(mt_rand(100000, 999999).time().$sHostName).'@'.$sHostName.'>';
	}

	/**
	 * @param \MailSo\Mime\Attachment $oAttachment
	 *
	 * @return \MailSo\Mime\Part
	 */
	private function createNewMessageAttachmentBody($oAttachment)
	{
		$oAttachmentPart = Part::NewInstance();

		$sFileName = $oAttachment->FileName();
		$sCID = $oAttachment->CID();

		$oContentTypeParameters = null;
		$oContentDispositionParameters = null;

		if (0 < strlen(trim($sFileName)))
		{
			$oContentTypeParameters =
				ParameterCollection::NewInstance()->Add(Parameter::NewInstance(
					\MailSo\Mime\Enumerations\Parameter::NAME, $sFileName));

			$oContentDispositionParameters =
				ParameterCollection::NewInstance()->Add(Parameter::NewInstance(
					\MailSo\Mime\Enumerations\Parameter::FILENAME, $sFileName));
		}

		$oAttachmentPart->Headers->Add(
			Header::NewInstance(\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
				$oAttachment->ContentType().';'.
				(($oContentTypeParameters) ? ' '.$oContentTypeParameters->ToString() : '')
			)
		);

		$oAttachmentPart->Headers->Add(
			Header::NewInstance(\MailSo\Mime\Enumerations\Header::CONTENT_DISPOSITION,
				($oAttachment->IsInline() ? 'inline' : 'attachment').';'.
				(($oContentDispositionParameters) ? ' '.$oContentDispositionParameters->ToString() : '')
			)
		);

		if (0 < strlen($sCID))
		{
			$oAttachmentPart->Headers->Add(
				Header::NewInstance(\MailSo\Mime\Enumerations\Header::CONTENT_ID, $sCID)
			);
		}

		$oAttachmentPart->Body = $oAttachment->Resource();

		if ('message/rfc822' !== strtolower($oAttachment->ContentType()))
		{
			$oAttachmentPart->Headers->Add(
				Header::NewInstance(
					\MailSo\Mime\Enumerations\Header::CONTENT_TRANSFER_ENCODING,
					\MailSo\Base\Enumerations\Encoding::BASE64
				)
			);

			if (is_resource($oAttachmentPart->Body))
			{
				$oAttachmentPart->Body =
					\MailSo\Base\StreamWrappers\Binary::CreateStream($oAttachmentPart->Body,
						\MailSo\Base\StreamWrappers\Binary::GetInlineDecodeOrEncodeFunctionName(
							\MailSo\Base\Enumerations\Encoding::BASE64, false));
			}
		}

		return $oAttachmentPart;
	}

	/**
	 * @param array $aAlternativeData
	 *
	 * @return \MailSo\Mime\Part
	 */
	private function createNewMessageAlternativePartBody($aAlternativeData)
	{
		$oAlternativePart = null;

		if (is_array($aAlternativeData) && isset($aAlternativeData[0]))
		{
			$oAlternativePart = Part::NewInstance();

			$oAlternativePart->Headers->Add(
				Header::NewInstance(\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
					$aAlternativeData[0].'; '.
					ParameterCollection::NewInstance()->Add(
						Parameter::NewInstance(
							\MailSo\Mime\Enumerations\Parameter::CHARSET,
							\MailSo\Base\Enumerations\Charset::UTF_8)
					)->ToString()
				)
			);

			$oAlternativePart->Body = null;
			if (isset($aAlternativeData[1]))
			{
				if (is_resource($aAlternativeData[1]))
				{
					$oAlternativePart->Body = $aAlternativeData[1];
				}
				else if (is_string($aAlternativeData[1]) && 0 < strlen($aAlternativeData[1]))
				{
					$oAlternativePart->Body =
						\MailSo\Base\ResourceRegistry::CreateMemoryResourceFromString($aAlternativeData[1]);
				}
			}

			if (isset($aAlternativeData[2]) && 0 < strlen($aAlternativeData[2]))
			{
				$oAlternativePart->Headers->Add(
					Header::NewInstance(\MailSo\Mime\Enumerations\Header::CONTENT_TRANSFER_ENCODING,
						$aAlternativeData[2]
					)
				);

				if (is_resource($oAlternativePart->Body))
				{
					$oAlternativePart->Body =
						\MailSo\Base\StreamWrappers\Binary::CreateStream($oAlternativePart->Body,
							\MailSo\Base\StreamWrappers\Binary::GetInlineDecodeOrEncodeFunctionName(
								$aAlternativeData[2], false));
				}
			}

			if (!is_resource($oAlternativePart->Body))
			{
				$oAlternativePart->Body =
					\MailSo\Base\ResourceRegistry::CreateMemoryResourceFromString('');
			}
		}

		return $oAlternativePart;
	}

	/**
	 * @param \MailSo\Mime\Part $oPlainPart
	 * @param \MailSo\Mime\Part $oHtmlPart
	 *
	 * @return \MailSo\Mime\Part
	 */
	private function createNewMessageSimpleOrAlternativeBody()
	{
		$oResultPart = null;
		if (1 < count($this->aAlternativeParts))
		{
			$oResultPart = Part::NewInstance();

			$oResultPart->Headers->Add(
				Header::NewInstance(\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
					\MailSo\Mime\Enumerations\MimeType::MULTIPART_ALTERNATIVE.'; '.
					ParameterCollection::NewInstance()->Add(
						Parameter::NewInstance(
							\MailSo\Mime\Enumerations\Parameter::BOUNDARY,
							$this->generateNewBoundary())
					)->ToString()
				)
			);

			foreach ($this->aAlternativeParts as $aAlternativeData)
			{
				$oAlternativePart = $this->createNewMessageAlternativePartBody($aAlternativeData);
				if ($oAlternativePart)
				{
					$oResultPart->SubParts->Add($oAlternativePart);
				}

				unset($oAlternativePart);
			}

		}
		else if (1 === count($this->aAlternativeParts))
		{
			$oAlternativePart = $this->createNewMessageAlternativePartBody($this->aAlternativeParts[0]);
			if ($oAlternativePart)
			{
				$oResultPart = $oAlternativePart;
			}
		}

		if (!$oResultPart)
		{
			if ($this->bAddEmptyTextPart)
			{
				$oResultPart = $this->createNewMessageAlternativePartBody(array(
					\MailSo\Mime\Enumerations\MimeType::TEXT_PLAIN, null
				));
			}
			else
			{
				$aAttachments = $this->oAttachmentCollection->CloneAsArray();
				if (1 === count($aAttachments) && isset($aAttachments[0]))
				{
					$this->oAttachmentCollection->Clear();

					$oResultPart = $this->createNewMessageAlternativePartBody(array(
						$aAttachments[0]->ContentType(), $aAttachments[0]->Resource()
					));
				}
			}
		}

		return $oResultPart;
	}

	/**
	 * @param \MailSo\Mime\Part $oIncPart
	 *
	 * @return \MailSo\Mime\Part
	 */
	private function createNewMessageRelatedBody($oIncPart)
	{
		$oResultPart = null;

		$aAttachments = $this->oAttachmentCollection->LinkedAttachments();
		if (0 < count($aAttachments))
		{
			$oResultPart = Part::NewInstance();

			$oResultPart->Headers->Add(
				Header::NewInstance(\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
					\MailSo\Mime\Enumerations\MimeType::MULTIPART_RELATED.'; '.
					ParameterCollection::NewInstance()->Add(
						Parameter::NewInstance(
							\MailSo\Mime\Enumerations\Parameter::BOUNDARY,
							$this->generateNewBoundary())
					)->ToString()
				)
			);

			$oResultPart->SubParts->Add($oIncPart);

			foreach ($aAttachments as $oAttachment)
			{
				$oResultPart->SubParts->Add($this->createNewMessageAttachmentBody($oAttachment));
			}
		}
		else
		{
			$oResultPart = $oIncPart;
		}

		return $oResultPart;
	}

	/**
	 * @param \MailSo\Mime\Part $oIncPart
	 *
	 * @return \MailSo\Mime\Part
	 */
	private function createNewMessageMixedBody($oIncPart)
	{
		$oResultPart = null;

		$aAttachments = $this->oAttachmentCollection->UnlinkedAttachments();
		if (0 < count($aAttachments))
		{
			$oResultPart = Part::NewInstance();

			$oResultPart->Headers->AddByName(\MailSo\Mime\Enumerations\Header::CONTENT_TYPE,
				\MailSo\Mime\Enumerations\MimeType::MULTIPART_MIXED.'; '.
				ParameterCollection::NewInstance()->Add(
					Parameter::NewInstance(
						\MailSo\Mime\Enumerations\Parameter::BOUNDARY,
						$this->generateNewBoundary())
				)->ToString()
			);

			$oResultPart->SubParts->Add($oIncPart);

			foreach ($aAttachments as $oAttachment)
			{
				$oResultPart->SubParts->Add($this->createNewMessageAttachmentBody($oAttachment));
			}
		}
		else
		{
			$oResultPart = $oIncPart;
		}

		return $oResultPart;
	}

	/**
	 * @param \MailSo\Mime\Part $oIncPart
	 * @param bool $bWithoutBcc = false
	 *
	 * @return \MailSo\Mime\Part
	 */
	private function setDefaultHeaders($oIncPart, $bWithoutBcc = false)
	{
		$oIncPart->Headers->SetByName(\MailSo\Mime\Enumerations\Header::DATE, \gmdate('r'), true);
		if (0 < \strlen($this->sMessageId))
		{
			$oIncPart->Headers->SetByName(\MailSo\Mime\Enumerations\Header::MESSAGE_ID, $this->sMessageId, true);
		}

		$oIncPart->Headers->SetByName(\MailSo\Mime\Enumerations\Header::X_MAILER,
			0 === \strlen($this->sCustomXMailer) ? \MailSo\Version::XMailer() : $this->sCustomXMailer, true);

		$oIncPart->Headers->SetByName(\MailSo\Mime\Enumerations\Header::MIME_VERSION, '1.0', true);

		foreach ($this->aHeadersValue as $sName => $mValue)
		{
			if (\is_object($mValue))
			{
				if ($mValue instanceof \MailSo\Mime\EmailCollection || $mValue instanceof \MailSo\Mime\Email ||
					$mValue instanceof \MailSo\Mime\ParameterCollection)
				{
					$mValue = $mValue->ToString();
				}
			}

			if (!($bWithoutBcc && \strtolower(\MailSo\Mime\Enumerations\Header::BCC) === \strtolower($sName)))
			{
				$oIncPart->Headers->SetByName($sName, (string) $mValue);
			}
		}

		return $oIncPart;
	}

	/**
	 * @param bool $bWithoutBcc = false
	 *
	 * @return \MailSo\Mime\Part
	 */
	public function ToPart($bWithoutBcc = false)
	{
		$oPart = $this->createNewMessageSimpleOrAlternativeBody();
		$oPart = $this->createNewMessageRelatedBody($oPart);
		$oPart = $this->createNewMessageMixedBody($oPart);
		$oPart = $this->setDefaultHeaders($oPart, $bWithoutBcc);

		return $oPart;
	}

	/**
	 * @param bool $bWithoutBcc = false
	 *
	 * @return resource
	 */
	public function ToStream($bWithoutBcc = false)
	{
		return $this->ToPart($bWithoutBcc)->ToStream();
	}

	/**
	 * @param bool $bWithoutBcc = false
	 *
	 * @return string
	 */
	public function ToString($bWithoutBcc = false)
	{
		return \stream_get_contents($this->ToStream($bWithoutBcc));
	}
}