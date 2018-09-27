<?php
  
namespace Chetu\Promo\Block\Adminhtml\Form\Element;
  
use Magento\Framework\Data\Form\Element\Date;

class DateTime extends Date {
   public function getElementHtml() {
	   $this->setDateFormat($this->localeDate->getDateFormat(\IntlDateFormatter::SHORT));
	   $this->setTimeFormat($this->localeDate->getTimeFormat(\IntlDateFormatter::SHORT));
	   return parent::getElementHtml();
   }
}