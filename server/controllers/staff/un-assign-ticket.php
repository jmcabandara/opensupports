<?php
use Respect\Validation\Validator as DataValidator;
DataValidator::with('CustomValidations', true);

/**
 * @api {post} /staff/un-assign-ticket Un-assign ticket
 * @apiVersion 4.1.0
 *
 * @apiName Un-assign ticket
 *
 * @apiGroup Staff
 *
 * @apiDescription This path un-assigns a ticket to the current staff member.
 *
 * @apiPermission staff1
 *
 * @apiParam {Number} ticketNumber Ticket number to un-assign.
 *
 * @apiUse NO_PERMISSION
 * @apiUse INVALID_TICKET
 *
 * @apiSuccess {Object} data Empty object
 *
 */

class UnAssignStaffController extends Controller {
    const PATH = '/un-assign-ticket';
    const METHOD = 'POST';

    public function validations() {
        return [
            'permission' => 'staff_1',
            'requestData' => [
                'ticketNumber' => [
                    'validation' => DataValidator::validTicketNumber(),
                    'error' => ERRORS::INVALID_TICKET
                ]
            ]
        ];
    }

    public function handler() {
        $ticketNumber = Controller::request('ticketNumber');
        $user = Controller::getLoggedUser();
        $ticket = Ticket::getByTicketNumber($ticketNumber);
        $owner = $ticket->owner;

        if($ticket->isOwner($user) || $user->level > 2) {
            if(!$ticket->isAuthor($user)) {
                $owner->sharedTicketList->remove($ticket);
                $owner->store();
            }

            $ticket->owner = null;
            $ticket->unread = !$ticket->isAuthor($user);

            $event = Ticketevent::getEvent(Ticketevent::UN_ASSIGN);
            $event->setProperties(array(
                'authorStaff' => $user,
                'date' => Date::getCurrentDate()
            ));

            $ticket->addEvent($event);
            $ticket->store();
            Response::respondSuccess();
        } else {
            throw new Exception(ERRORS::NO_PERMISSION);
        }
    }
}
