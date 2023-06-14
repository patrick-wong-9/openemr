<?php

/**
 * FhirMedicationRequestSerializer.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Patrick Wong <patrick@contrastai.com>
 * @copyright Copyright (c) 2023 Patrick Wong 
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
 
 namespace OpenEMR\Services\FHIR\Serialization;
 
 use OpenEMR\FHIR\R4\FHIRDomainResource\FHIRMedicationRequest;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRAddress;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRCode;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRCodeableConcept;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRCoding;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRContactPoint;
 use OpenEMR\FHIR\R4\FHIRResource\FHIRDosage;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRHumanName;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRIdentifier;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRPeriod;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRReference;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRUri;
 use OpenEMR\FHIR\R4\FHIRElement\FHIRString;
 use OpenEMR\Common\Logging\SystemLogger;
 use OpenEMR\Services\FHIR\UtilsService;

 class FhirMedicationRequestSerializer
 {
     public static function serialize(FHIRMedicationRequest $object)
     {
         return $object->jsonSerialize();
     }
 
     // TODO: @adunsulag this is very painful to hydrate our objects.  It would be better if we used something like
     // the symfony serializer @see https://symfony.com/doc/current/components/serializer.html#deserializing-in-an-existing-object
     // If we want to start type safing things a LOT more and working with objects instead of arrays we could include that
     // library, but for this one off... perhaps we don't need it so much.  Once we start dealing with POST/PUT its going
     // to become a much bigger deal to go from JSON to type safed objects especially in terms of error elimination...
     /**
      * Takes a fhir json representing an organization and returns the populated the resource
      * @param $fhirJson
      * @return FHIRMedicationRequest
      */
     public static function deserialize($fhirJson)
     {
        (new SystemLogger())->debug("IN THE SERIALIZER LESSSS GOOOO");

        $identifiers = $fhirJson['identifier'] ?? [];
        $categories = $fhirJson['category'] ?? [];
        $supportingInformation = $fhirJson['supportingInformation'] ?? [];
        $reasonCode = $fhirJson['reasonCode'] ?? [];
        $reasonReference = $fhirJson['reasonReference'] ?? [];
        $instantiatesCanonical = $fhirJson['instantiatesCanonical'] ?? [];
        $instantiatesUri = $fhirJson['instantiatesUri'] ?? [];
        $basedOn = $fhirJson['basedOn'] ?? [];
        $insurance = $fhirJson['insurance'] ?? [];
        $note = $fhirJson['note'] ?? [];
        $dosageInstruction = $fhirJson['dosageInstruction'] ?? [];
        $detectedIssue = $fhirJson['detectedIssue'] ?? [];
        $eventHistory = $fhirJson['eventHistory'] ?? [];


        $medicationCodeableConcept = $fhirJson['medicationCodeableConcept'] ?? [];
        unset($fhirJson['medicationCodeableConcept']);
        $subject = $fhirJson['subject'] ?? [];
        unset($fhirJson['subject']);
        $authoredOn = $fhirJson['authoredOn'] ?? [];
        unset($fhirJson['authoredOn']);
        
        unset($fhirJson['identifier']);
        unset($fhirJson['category']);
        unset($fhirJson['supportingInformation']);
        unset($fhirJson['reasonCode']);
        unset($fhirJson['reasonReference']);
        unset($fhirJson['instantiatesCanonical']);
        unset($fhirJson['instantiatesUri']);
        unset($fhirJson['basedOn']);
        unset($fhirJson['insurance']);
        unset($fhirJson['note']);
        unset($fhirJson['dosageInstruction']);
        unset($fhirJson['detectedIssue']);
        unset($fhirJson['eventHistory']);


        
        // TODO: better way to do this would be modifying the FHIRResource classes themselves to iterate
        // Doing this just for testing purposes first.
        $medicationRequest = new FHIRMedicationRequest($fhirJson);

        // * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRIdentifier[]
        foreach ($identifiers as $item) {
            $type = $item['type'] ?? [];
            $coding = $type['coding'] ?? [];
            unset($type['coding']);
            $codeableConcept = new FHIRCodeableConcept($type);
            foreach ($coding as $codingItem) {
                $codingItem = new FHIRCoding($codingItem);
                $codeableConcept->addCoding($codingItem);
            }
            $obj = new FHIRIdentifier($type);
            $obj->setType($codeableConcept);
            $medicationRequest->addIdentifier($obj);
        }

        //     * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRCodeableConcept[]
        foreach ($categories as $item){
            $codeableConcept = new FHIRCodeableConcept();
            $coding = $item['coding'] ?? [];
            $text = $item['text'] ?? [];
            foreach ($coding as $cItem) {
                $codingItem = UtilsService::createCoding($cItem['code'], $cItem['display'], $cItem['system']);
                $codeableConcept->addCoding($codingItem);
            }
            $codeableConcept->setText(new FHIRString($text));
            $medicationRequest->addCategory($codeableConcept);
        }

        //     * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRReference[]
        // need nested deserializatilon for identifier still
        foreach ($supportingInformation as $item) {
            $type = new FHIRUri($item['type']);
            $reference = new FHIRReference();
            $reference->setType($type);
            // setIdentifier()
            // setReference() nested resource
            $medicationRequest->addSupportingInformation($reference);
        }

        //     * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRCodeableConcept[]
        foreach ($reasonCode as $item){
            $codeableConcept = new FHIRCodeableConcept();
            $coding = $item['coding'] ?? [];
            $text = $item['text'] ?? [];
            foreach ($coding as $cItem) {
                $codingItem = new FHIRCoding($cItem);
                $codeableConcept->addCoding($codingItem);
            }
            $codeableConcept->setText($text);
            $medicationRequest->addCategory($codeableConcept);
        }

        // * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRReference[]
        foreach ($reasonReference as $item){
            $identifier = UtilsService::createIdentifierFromArray($item['identifier']);

            // if(!empty($identifier['type'])){
            //     $cc = new FHIRCodeableConcept();
            //     $coding = $item['coding'] ?? [];
            //     $text = $item['text'] ?? [];
            //     foreach ($coding as $cItem) {
            //         $codingItem = new FHIRCoding($cItem);
            //         $cc->addCoding($codingItem);
            //     }
            //     $cc->setText($text);
            // }
            // $identifier->setType($cc)
            
            $reasonRef = new FHIRReference($item);
            if(!empty($item['type'])){
                $uri = new FHIRUri($item['type']);
                $reasonRef->setType($uri);
            }
            $reasonRef->setIdentifier($identifier);

            $medicationRequest->addReasonReference($reasonRef);
        }

        //* @var \OpenEMR\FHIR\R4\FHIRResource\FHIRDosage[]
        foreach($dosageInstruction as $item){
            (new SystemLogger())->error("in DOSAGE INSTRUCTIONS FOR LOOP");
            (new SystemLogger())->error(print_r($item, true));
            (new SystemLogger())->error("end of DOSAGE INSTRUCTIONS FOR LOOP");
            $medicationRequest->addDosageInstruction(UtilsService::createDosageInstructionFromArray($item));
        }

        //     * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRCanonical[]
        foreach ($instantiatesCanonical as $item){
            $type = $item['type'] ?? [];
            $canonical = new FHIRCanonical($type);
            $medicationRequest->addInstantiatesCanonical($canonical);
        }
        //     * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRUri[]
        foreach ($instantiatesUri as $item) {
            $type = $item['type'] ?? [];
            $uri = new FHIRUri($type);
            $medicationRequest->addInstantiatesUri($uri);
        }

        // * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRReference[]
        foreach ($basedOn as $item){
            $type = $item['type'] ?? [];
            $based = new FHIRReference($type);
            $medicationRequest->addBasedOn($based);
        } 

        // * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRReference[]
        foreach ($insurance as $item){
            $type = $item['type'] ?? [];
            $ins = new FHIRReference($type);
            $medicationRequest->addInsurance($ins);
        } 

        foreach ($note as $item) {
            $type = $item['type'] ?? [];
            $ann = new FHIRAnnotation($type);
            $medicationRequest->addAnnotation($ann);
        }

        foreach($dosageInstruction as $item) {
            $type = $item['type'] ?? [];
            unset($item['type']);
            $dosage = new FHIRDosage($type);
            $medicationRequest->addDosageInstruction($dosage);
        }

        // * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRReference[]
        foreach ($detectedIssue as $item){
            $type = $item['type'] ?? [];
            $detected = new FHIRReference($type);
            $medicationRequest->addDetectedIssue($detected);
        }      

        // * @var \OpenEMR\FHIR\R4\FHIRElement\FHIRReference[]
        foreach ($eventHistory as $item){
            $type = $item['type'] ?? [];
            $history = new FHIRReference($type);
            $medicationRequest->addDetectedIssue($history);
        }


        $codeableConcept = new FHIRCodeableConcept();
        foreach ($medicationCodeableConcept['coding'] as $item) {
            $temp = print_r($item, true);
            (new SystemLogger())->debug($temp);
            $codingItem = new FHIRCoding();
            if(!empty($item['system'])) $codingItem->setSystem(new FHIRUri($item['system']));
            if(!empty($item['display'])) $codingItem->setDisplay(new FHIRString($item['display']));
            if(!empty($item['code'])) $codingItem->setCode(($item['code']));
            $codeableConcept->addCoding($codingItem);
        }
        $medicationRequest->setMedicationCodeableConcept($codeableConcept);

        
        // foreach($medicationCodeableConcept as $item){
        //     $object = UtilsService::createCodeableConcept($item);
        //     $medicationRequest->setMedicationCodeableConcept($object);
        // }

        // only dosage has nested fhir classes in the setters.
        // idea is if all nested Fhir classes returned FHIR objects,
        // deserializing from JSON to FHIRClass objects works.
        $uuid = UtilsService::getUuidFromReferenceString($subject['reference']);
        $object = UtilsService::createRelativeReference($subject['type'], $uuid);
        $medicationRequest->setSubject($object);
 
        $datetime_regex = "([0-9]([0-9]([0-9][1-9]|[1-9]0)|[1-9]00)|[1-9]000)(-(0[1-9]|1[0-2])(-(0[1-9]|[1-2][0-9]|3[0-1])(T([01][0-9]|2[0-3]):[0-5][0-9]:([0-5][0-9]|60)(\.[0-9]+)?(Z|(\+|-)((0[0-9]|1[0-3]):[0-5][0-9]|14:00)))?)?)?";    
        if(preg_match($datetime_regex, $authoredOn)) {
            $object = new FHIRDateTime();
            $object->setValue($authoredOn);
            $medicationRequest->setAuthoredOn($object);
        } 


        return $medicationRequest;
     }
 }
 