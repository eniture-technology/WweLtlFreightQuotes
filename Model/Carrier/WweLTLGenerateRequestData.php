<?php

namespace Eniture\WweLtlFreightQuotes\Model\Carrier;

use Magento\Store\Model\ScopeInterface;

/**
 * class that generated request data
 */
class WweLTLGenerateRequestData
{
    /**
     * @var Object
     */
    private $registry;
    /**
     * @var Object
     */
    private $moduleManager;
    /**
     * @var object
     */
    private $request;
    /**
     * @var object
     */
    private $scopeConfig;

    private $appConfigData = [];

    /**
     * constructor of class that accepts request object
     * @param $scopeConfig
     * @param $registry
     * @param $moduleManager
     * @param $request
     */
    public function _init(
        $scopeConfig,
        $registry,
        $moduleManager,
        $request
    )
    {
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->moduleManager = $moduleManager;
        $this->request = $request;
    }

    /**
     * function that generates Wwe array
     * @return array
     */
    public function generateEnitureArray()
    {
        $getDistance = 0;
        return [
            'licenseKey' => $this->getConfigData('WweLtLicenseKey'),
            'serverName' => $this->request->getServer('SERVER_NAME'),
            'carrierMode' => 'pro', //$this->getConfigData('WweltlAccessLevel')
            'quotestType' => 'ltl', // ltl / small
            'version' => '1.0.0',
            'returnQuotesOnExceedWeight' => $this->getConfigData('weightExeeds'),
            'liftGateAsAnOption' => $this->getConfigData('OfferLiftgateAsAnOption'),
            'api' => $this->getApiInfoArr(),
            'getDistance' => $getDistance,
        ];
    }

    /**
     * Azeem
     * this function sets carriers array globally
     * @param $postData
     * @return array
     */

    public function wweLtlSetCarriersGlobally($postData)
    {
        $EnGlobalCarriers = $this->registry->registry('enitureCarriers');

        if ($EnGlobalCarriers === null) {
            $this->registry->register('enitureCarriers', $postData);
        } else {
            $this->registry->unregister('enitureCarriers');
            $setEnCarriers = array_merge($postData, $EnGlobalCarriers);
            $this->registry->register('enitureCarriers', $setEnCarriers);
        }
        return $this->registry->registry('enitureCarriers');
    }

    /**
     * function for generate request array
     * @param $request
     * @param $originArr
     * @param $itemsArr
     * @param $cart
     * @return array|bool
     */
    public function generateRequestArray($request, $originArr, $itemsArr, $cart)
    {
        if (count($originArr['originAddress']) > 1) {
            $whIDs = [];
            foreach ($originArr['originAddress'] as $wh) {
                $whIDs[] = $wh['locationId'];
            }
            if (count(array_unique($whIDs)) > 1) {
                foreach ($originArr['originAddress'] as $id => $wh) {
                    if (isset($wh['InstorPickupLocalDelivery'])) {
                        $originArr['originAddress'][$id]['InstorPickupLocalDelivery'] = [];
                    }
                }
            }
        }
        $carriers = $this->registry->registry('enitureCarriers');
        $carriers['wweLTL'] = $originArr;
        $receiverAddress = $this->getReceiverData($request);

        $autoResidential = $liftGateWithAuto = '0';
        if ($this->autoResidentialDelivery()) {
            $autoResidential = '1';
            $liftGateWithAuto = $this->getConfigData('RADforLiftgate') ?? '0';

            if ($this->registry->registry('radForLiftgate') === null) {
                $this->registry->register('radForLiftgate', $liftGateWithAuto);
            }
        }
        $smartPost = $this->registry->registry('fedexSmartPost');

        return [
            'apiVersion' => '2.0',
            'platform' => 'magento2',
            'binPackagingMultiCarrier' => $this->binPackSuspend(),
            'autoResidentials' => $autoResidential,
            'liftGateWithAutoResidentials' => $liftGateWithAuto,
            'FedexOneRatePricing' => $smartPost,
            'FedexSmartPostPricing' => $smartPost,
            'requestKey' => $cart->getQuote()->getId(),
            'carriers' => $carriers,
            'receiverAddress' => $receiverAddress,
            'commdityDetails' => $itemsArr,
        ];
    }

    /**
     * @return string
     */
    public function binPackSuspend()
    {
        $return = "0";
        if ($this->moduleManager->isEnabled('Eniture_BoxSizes')) {
            $return = $this->scopeConfig->getValue("binPackaging/suspend/value", ScopeInterface::SCOPE_STORE) == "no" ? "1" : "0";
        }
        return $return;
    }

    /**
     * Azeem
     * this function returns active eniture modules count
     * @return int
     */
    public function getActiveEnitureModulesCount()
    {
        $activeModules = array_keys($this->dataHelper->getActiveCarriersForENCount());
        $activeEnModulesArr = array_filter($activeModules, function ($moduleName) {
            if (substr($moduleName, 0, 2) == 'EN') {
                return true;
            }
            return false;
        });

        return count($activeEnModulesArr);
    }

    /**
     * this function returns active Eniture modules count
     * @return int
     */
    public function autoResidentialDelivery()
    {
        $autoDetectResi = 0;
        if ($this->moduleManager->isEnabled('Eniture_ResidentialAddressDetection')) {
            $suspendPath = "resaddressdetection/suspend/value";
            $autoResidential = $this->scopeConfig->getValue($suspendPath, ScopeInterface::SCOPE_STORE);
            if ($autoResidential != null && $autoResidential == 'no') {
                $autoDetectResi = 1;
            }
        }
        return $autoDetectResi;
    }

    /**
     * Azeem
     * This function returns carriers array if have not empty origin address
     * @return array
     */
    public function getCarriersArray()
    {
        $carriersArr = $this->registry->registry('enitureCarriers');
        $newCarriersArr = [];
        foreach ($carriersArr as $carrKey => $carrArr) {
            $notHaveEmptyOrigin = true;
            foreach ($carrArr['originAddress'] as $value) {
                if (empty($value['senderZip'])) {
                    $notHaveEmptyOrigin = false;
                }
            }
            if ($notHaveEmptyOrigin) {
                $newCarriersArr[$carrKey] = $carrArr;
            }
        }
        return $newCarriersArr;
    }

    /**
     * function that returns API array
     * @return array
     */
    public function getApiInfoArr()
    {
        //Todo: need to review this function
        $accessorials = [];
        if (!$this->autoResidentialDelivery()) {
            ($this->getConfigData('residentialDlvry')) ? array_push($accessorials, 'RESDEL') : '';
        }
        ($this->getConfigData('liftGate')) ? array_push($accessorials, 'LFTGATDEST') : '';

        if ($this->autoResidentialDelivery()) {
            $residential = 'N';
        } else {
            $residential = ($this->getConfigData('residentialDlvry')) ? 'Y' : 'N';
        }

        $liftGate = ($this->getConfigData('liftGate') ||
            $this->getConfigData('OfferLiftgateAsAnOption')) ? 'Y' : 'N';

        $shipperRelation = $this->getConfigData('shipperRelation');

        $apiArray = [
            'speed_freight_username' => $this->getConfigData('WweLtUsername'),
            'speed_freight_password' => $this->getConfigData('WweLtPassword'),
            'speed_freight_authentication_key' => $this->getConfigData('WweLtAuthenticationKey'),
            'speed_freight_account_number' => $this->getConfigData('WweLtAccountNumber'),
            'speed_freight_residential_delivery' => $residential,
            'speed_freight_lift_gate_delivery' => $liftGate,
        ];

        if ($shipperRelation == 'ThirdParty') {
            $apiArray['payerAddress'] = [
                'name' => 'name',
                'addressLine' => 'addressLine',
                'country' => $this->getConfigData('thirdPartyCountry'),
                'zip' => $this->getConfigData('thirdPartyPostalCode'),
                'state' => $this->getConfigData('thirdPartyState'),
                'city' => $this->getConfigData('thirdPartyCity')
            ];
        }

        return $apiArray;
    }

    /**
     * function return service data
     * @param $fieldId
     * @return string
     */
    public function getConfigData($fieldId)
    {
        $secThreeIds = ['residentialDlvry', 'liftGate', 'weightExeeds', 'offerLiftGate', 'RADforLiftgate','OfferLiftgateAsAnOption'];
        if (in_array($fieldId, $secThreeIds)) {
            $sectionId = 'WweLtQuoteSetting';
            $groupId = 'third';
        } else {
            $sectionId = 'WweLtConnSettings';
            $groupId = 'first';
        }

        return $this->scopeConfig->getValue("$sectionId/$groupId/$fieldId", ScopeInterface::SCOPE_STORE);
    }

    /**
     * This function returns Receiver Data Array
     * @param object $request
     * @return array
     */
    public function getReceiverData($request)
    {
        $addressTypePath = "resaddressdetection/addressType/value";
        $addressType = $this->scopeConfig->getValue($addressTypePath, ScopeInterface::SCOPE_STORE);
        return [
            'addressLine' => $request->getDestStreet(),
            'receiverCity' => $request->getDestCity(),
            'receiverState' => $request->getDestRegionCode(),
            'receiverZip' => preg_replace('/\s+/', '', $request->getDestPostcode()),
            'receiverCountryCode' => $request->getDestCountryId(),
            'defaultRADAddressType' => $addressType ?? 'residential', //get value from RAD
        ];
    }

    /**
     *
     * @param type $request
     * @return int
     */
    public function checkEnablePickupDelivery($request)
    {
        $getDistance = 0;
        $originArr = $this->registry->registry('shipmentOrigin');
        $idMatchArr = [];

        foreach ($originArr as $origin) {
            if (count($idMatchArr) == 0) {
                $idMatchArr = $origin;
            } else {
                $locationId = $origin['locationId'];
                if ($locationId != $idMatchArr['locationId']) {
                    return 0;
                }
            }
        }

        // Register origin for Addon
        if ($this->registry->registry('pickupDeliveryLocation') === null) {
            $this->registry->register('pickupDeliveryLocation', $idMatchArr);
            $_SESSION['pickupDeliveryLocation'] = $idMatchArr;
        }

        $locationId = $idMatchArr['locationId'];
        $readresult = $this->_connection->query("SELECT enable_store_pickup, miles_store_pickup, match_postal_store_pickup, checkout_desc_store_pickup, enable_local_delivery, miles_local_delivery, match_postal_local_delivery, checkout_desc_local_delivery, fee_local_delivery, suppress_local_delivery FROM " . $this->whTableName . " WHERE warehouse_id IN ('" . $locationId . "')");
        $pickupDlvryOptions = $readresult->fetch();

        $instorePickup = $this->addInstorePickup($pickupDlvryOptions, $request);
        $localDelivery = $this->addLocalDelivery($pickupDlvryOptions, $request);

        if ($instorePickup == 'yes' || $localDelivery == 'yes') {
            $getDistance = 1;
        }

        return $getDistance;
    }

    /**
     *
     * @param $pickupDeliveryOptions
     * @param $request
     * @return string
     */
    public function addInstorePickup($pickupDeliveryOptions, $request)
    {
        $receiver = $this->getReceiverData($request);

        $getMilesGoogleApi = 'no';
        $pickupEnable = $pickupDeliveryOptions['enable_store_pickup'];
        $inStoreZips = $pickupDeliveryOptions['match_postal_store_pickup'];
        if ($pickupEnable == 1) {
            $matchPostals = explode(',', $inStoreZips);
            if (empty($inStoreZips) || !in_array($receiver['receiverZip'], $matchPostals)) {
                $getMilesGoogleApi = 'yes';
            }
        }
        return $getMilesGoogleApi;
    }

    /**
     *
     * @param $pickupDeliveryOptions
     * @param $request
     * @return string
     */
    public function addLocalDelivery($pickupDeliveryOptions, $request)
    {
        $receiver = $this->getReceiverData($request);
        $getMilesGoogleApi = 'no';
        $pickupEnable = $pickupDeliveryOptions['enable_local_delivery'];
        $localDeliveryZips = $pickupDeliveryOptions['match_postal_local_delivery'];
        if ($pickupEnable == 1) {
            $matchPostals = explode(',', $localDeliveryZips);
            if (empty($localDeliveryZips) || !in_array($receiver['receiverZip'], $matchPostals)) {
                $getMilesGoogleApi = 'yes';
            }
        }
        return $getMilesGoogleApi;
    }
}