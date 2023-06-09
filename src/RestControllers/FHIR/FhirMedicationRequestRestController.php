<?php

/**
 * FhirMedicationRequestRestController
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Yash Bothra <yashrajbothra786@gmail.com>
 * @copyright Copyright (c) 2020 Yash Bothra <yashrajbothra786@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\RestControllers\FHIR;

use OpenEMR\Services\FHIR\FhirMedicationRequestService;
use OpenEMR\Services\FHIR\FhirResourcesService;
use OpenEMR\RestControllers\RestControllerHelper;
use OpenEMR\FHIR\R4\FHIRResource\FHIRBundle\FHIRBundleEntry;
use OpenEMR\Services\FHIR\FhirValidationService;
use OpenEMR\Services\FHIR\Serialization\FhirMedicationRequestSerializer;
use OpenEMR\Common\Logging\SystemLogger;

class FhirMedicationRequestRestController
{
    private $fhirService;
    private $fhirMedicationRequestService;
    private $fhirValidate;

    public function __construct()
    {
        $this->fhirService = new FhirResourcesService();
        $this->fhirMedicationRequestService = new FhirMedicationRequestService();
        $this->fhirValidate = new FhirValidationService();
    }

    /**
     * Creates a new FHIR MedicationRequest resource
     * @param $fhirJson The FHIR MedicationRequest resource
     * @returns 201 if the resource is created, 400 if the resource is invalid
     */
    public function post($fhirJson)
    {
        (new SystemLogger())->debug("start of post() in MR controller............................");
       // (new SystemLogger())->debug(print_r($fhirJson));
        // $fhirValidate = $this->fhirValidate->validate($fhirJson);
        // (new SystemLogger())->debug("The debugger is here");
        // (new SystemLogger())->debug("${fhirValidate}");
        // if (!empty($fhirValidate)) {
        //     return RestControllerHelper::responseHandler($fhirValidate, null, 400);
        // }
        //print_r($fhirJson);
        $object = FhirMedicationRequestSerializer::deserialize($fhirJson);
        print_r($object);
       // (new SystemLogger())->debug(print_r($object));
        $processingResult = $this->fhirMedicationRequestService->insert($object);
        return RestControllerHelper::handleFhirProcessingResult($processingResult, 201);
    }
    
    /**
     * Queries for a single FHIR medication resource by FHIR id
     * @param $fhirId The FHIR medication resource id (uuid)
     * @param $puuidBind - Optional variable to only allow visibility of the patient with this puuid.
     * @returns 200 if the operation completes successfully
     */
    public function getOne($fhirId, $puuidBind = null)
    {
        $processingResult = $this->fhirMedicationRequestService->getOne($fhirId, $puuidBind);
        return RestControllerHelper::handleFhirProcessingResult($processingResult, 200);
    }

    /**
     * Queries for FHIR medication resources using various search parameters.
     * Search parameters include:
     * - patient (puuid)
     * @param $puuidBind - Optional variable to only allow visibility of the patient with this puuid.
     * @return FHIR bundle with query results, if found
     */
    public function getAll($searchParams, $puuidBind = null)
    {
        $processingResult = $this->fhirMedicationRequestService->getAll($searchParams, $puuidBind);
        $bundleEntries = array();
        foreach ($processingResult->getData() as $index => $searchResult) {
            $bundleEntry = [
                'fullUrl' =>  $GLOBALS['site_addr_oath'] . ($_SERVER['REDIRECT_URL'] ?? '') . '/' . $searchResult->getId(),
                'resource' => $searchResult
            ];
            $fhirBundleEntry = new FHIRBundleEntry($bundleEntry);
            array_push($bundleEntries, $fhirBundleEntry);
        }
        $bundleSearchResult = $this->fhirService->createBundle('Medication', $bundleEntries, false);
        $searchResponseBody = RestControllerHelper::responseHandler($bundleSearchResult, null, 200);
        return $searchResponseBody;
    }
}
