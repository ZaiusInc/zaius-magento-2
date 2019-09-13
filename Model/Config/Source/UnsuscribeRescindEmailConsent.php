<?php
namespace Zaius\Engage\Model\Config\Source;
class UnsuscribeRescindEmailConsent implements \Magento\Framework\Option\ArrayInterface
{
    const UNSUSCRIBE_YES = 'on';
    const UNSUSCRIBE_NO = 'off';
    const UNSUSCRIBE_AUTO = 'auto';

    /**
     * Retrieve Custom Option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'on', 'label' => __('On')],
            ['value' => 'off', 'label' => __('Off')],
            ['value' => 'auto', 'label' => __('Auto')]
        ];
    }
}