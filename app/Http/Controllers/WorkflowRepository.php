<?php
/*
 * Copyright (c) KUBO Atsuhiro <kubo@iteman.jp> and contributors,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace App\Http\Controllers;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Definition\Bpmn2Reader;
use PHPMentors\Workflower\Workflow\WorkflowBuilder;
use PHPMentors\Workflower\Workflow\WorkflowRepositoryInterface;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    /**
     * @var array
     */
    private $workflows = array();

    public function __construct()
    {
        $this->add($this->createLoanRequestProcess());
        $this->add($this->createMultipleWorkItemsProcess());
        $this->add($this->createServiceTasksProcess());
        $this->add($this->createNoLanesProcess());
        $this->add($this->createSendTasksProcess());
    }

    /**
     * {@inheritdoc}
     */
    public function add(EntityInterface $entity)
    {
        //assert($entity instanceof Workflow);

        $this->workflows[$entity->getId()] = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(EntityInterface $entity)
    {
        //assert($entity instanceof Workflow);
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        if (!array_key_exists($id, $this->workflows)) {
            return null;
        }

        return $this->workflows[$id];
    }

    /**
     * @return Workflow
     */
    private function createLoanRequestProcess()
    {
        $workflowBuilder = new WorkflowBuilder();
        $workflowBuilder->setWorkflowId('LoanRequestProcess');
        $workflowBuilder->setWorkflowName('Loan Request Process');
        $workflowBuilder->addRole('ROLE_BRANCH', 'Branch');
        $workflowBuilder->addRole('ROLE_CREDIT_FACTORY', 'Credit Factory');
        $workflowBuilder->addRole('ROLE_BACK_OFFICE', 'Back Office');
        $workflowBuilder->addStartEvent('Start', 'ROLE_BRANCH');
        $workflowBuilder->addTask('RecordLoanApplicationInformation', 'ROLE_BRANCH', 'Record Loan Application Information');
        $workflowBuilder->addTask('CheckApplicantInformation', 'ROLE_BRANCH', 'Check Applicant Information');
        $workflowBuilder->addTask('LoanStudy', 'ROLE_CREDIT_FACTORY', 'Loan Study');
        $workflowBuilder->addTask('InformRejection', 'ROLE_CREDIT_FACTORY', 'Inform Rejection');
        $workflowBuilder->addTask('Disbursement', 'ROLE_BACK_OFFICE', 'Disbursement');
        $workflowBuilder->addExclusiveGateway('ResultOfVerification', 'ROLE_BRANCH', 'Result of Verification', 'ResultOfVerification.LoanStudy');
        $workflowBuilder->addExclusiveGateway('ApplicaionApproved', 'ROLE_CREDIT_FACTORY', 'Applicaion Approved?', 'ApplicaionApproved.Disbursement');
        $workflowBuilder->addEndEvent('End', 'ROLE_CREDIT_FACTORY');
        $workflowBuilder->addSequenceFlow('Start', 'RecordLoanApplicationInformation', 'Start.RecordLoanApplicationInformation');
        $workflowBuilder->addSequenceFlow('RecordLoanApplicationInformation', 'CheckApplicantInformation', 'RecordLoanApplicationInformation.CheckApplicantInformation');
        $workflowBuilder->addSequenceFlow('CheckApplicantInformation', 'ResultOfVerification', 'CheckApplicantInformation.ResultOfVerification');
        $workflowBuilder->addSequenceFlow('ResultOfVerification', 'LoanStudy', 'ResultOfVerification.LoanStudy', 'Ok');
        $workflowBuilder->addSequenceFlow('ResultOfVerification', 'End', 'ResultOfVerification.End', 'Rejected', 'rejected === true');
        $workflowBuilder->addSequenceFlow('LoanStudy', 'ApplicaionApproved', 'LoanStudy.ApplicaionApproved');
        $workflowBuilder->addSequenceFlow('ApplicaionApproved', 'Disbursement', 'ApplicaionApproved.Disbursement', 'Ok');
        $workflowBuilder->addSequenceFlow('ApplicaionApproved', 'InformRejection', 'ApplicaionApproved.InformRejection', 'Rejected', 'rejected === true');
        $workflowBuilder->addSequenceFlow('InformRejection', 'End', 'InformRejection.End');
        $workflowBuilder->addSequenceFlow('Disbursement', 'End', 'Disbursement.End');

        return $workflowBuilder->build();
    }

    /**
     * @return Workflow
     */
    private function createMultipleWorkItemsProcess()
    {
        $workflowBuilder = new WorkflowBuilder();
        $workflowBuilder->setWorkflowId('MultipleWorkItemsProcess');
        $workflowBuilder->addRole('ROLE_USER', 'User');
        $workflowBuilder->addStartEvent('Start', 'ROLE_USER');
        $workflowBuilder->addTask('Task1', 'ROLE_USER');
        $workflowBuilder->addTask('Task2', 'ROLE_USER', null, 'Task2.End');
        $workflowBuilder->addEndEvent('End', 'ROLE_USER');
        $workflowBuilder->addSequenceFlow('Start', 'Task1', 'Start.Task1');
        $workflowBuilder->addSequenceFlow('Task1', 'Task2', 'Task1.Task2');
        $workflowBuilder->addSequenceFlow('Task2', 'End', 'Task2.End');
        $workflowBuilder->addSequenceFlow('Task2', 'Task1', 'Task2.Task1', null, 'satisfied !== true');

        return $workflowBuilder->build();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 1.2.0
     */
    private function createServiceTasksProcess()
    {
        $bpmn2Reader = new Bpmn2Reader();

        return $bpmn2Reader->read('F:/App/xampp/htdocs/erp/vendor/phpmentors/workflower/tests/Resources/config/workflower/ServiceTasksProcess.bpmn');
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 1.3.0
     */
    private function createNoLanesProcess()
    {
        $bpmn2Reader = new Bpmn2Reader();

        return $bpmn2Reader->read('F:/App/xampp/htdocs/erp/vendor/phpmentors/workflower/tests/Resources/config/workflower/NoLanesProcess.bpmn');
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 1.3.0
     */
    private function createSendTasksProcess()
    {
        $bpmn2Reader = new Bpmn2Reader();

        return $bpmn2Reader->read('F:/App/xampp/htdocs/erp/vendor/phpmentors/workflower/tests/Resources/config/workflower/SendTasksProcess.bpmn');
    }
}
