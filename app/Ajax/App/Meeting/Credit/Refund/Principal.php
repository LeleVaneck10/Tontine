<?php

namespace App\Ajax\App\Meeting\Credit\Refund;

use App\Ajax\CallableClass;
use App\Ajax\App\Meeting\Credit\Loan;
use Siak\Tontine\Model\Session as SessionModel;
use Siak\Tontine\Service\Meeting\Credit\RefundService;
use Siak\Tontine\Validation\Meeting\DebtValidator;

use function Jaxon\jq;
use function trans;

/**
 * @databag meeting
 * @databag refund
 * @before getSession
 */
class Principal extends CallableClass
{
    /**
     * @var RefundService
     */
    protected RefundService $refundService;

    /**
     * @var DebtValidator
     */
    protected DebtValidator $validator;

    /**
     * @var SessionModel|null
     */
    protected ?SessionModel $session = null;

    /**
     * The constructor
     *
     * @param RefundService $refundService
     */
    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * @return void
     */
    protected function getSession()
    {
        $sessionId = $this->bag('meeting')->get('session.id');
        $this->session = $this->refundService->getSession($sessionId);
    }

    /**
     * @exclude
     */
    public function show(SessionModel $session)
    {
        $this->session = $session;

        return $this->home();
    }

    public function home()
    {
        $html = $this->view()->render('tontine.pages.meeting.refund.home')
            ->with('session', $this->session)
            ->with('type', 'principal');
        $this->response->html('meeting-principal-refunds', $html);
        $this->jq('#btn-principal-refunds-refresh')->click($this->rq()->home());
        $this->jq('#btn-principal-refunds-filter')->click($this->rq()->toggleFilter());

        return $this->page(1);
    }

    /**
     * @param int $pageNumber
     *
     * @return mixed
     */
    public function page(int $pageNumber = 0)
    {
        $filtered = $this->bag('refund')->get('principal.filter', null);
        $debtCount = $this->refundService->getPrincipalDebtCount($this->session, $filtered);
        [$pageNumber, $perPage] = $this->pageNumber($pageNumber, $debtCount, 'refund', 'principal.page');
        $debts = $this->refundService->getPrincipalDebts($this->session, $filtered, $pageNumber);
        $pagination = $this->rq()->page()->paginate($pageNumber, $perPage, $debtCount);

        $html = $this->view()->render('tontine.pages.meeting.refund.page', [
            'session' => $this->session,
            'debts' => $debts,
            'type' => 'principal',
            'pagination' => $pagination,
        ]);
        $this->response->html('meeting-principal-debts-page', $html);

        $debtId = jq()->parent()->attr('data-debt-id')->toInt();
        $this->jq('.btn-add-principal-refund')->click($this->rq()->createRefund($debtId));
        $this->jq('.btn-del-principal-refund')->click($this->rq()->deleteRefund($debtId));

        return $this->response;
    }

    public function toggleFilter()
    {
        $filtered = $this->bag('refund')->get('principal.filter', null);
        // Switch between null, true and false
        $filtered = $filtered === null ? true : ($filtered === true ? false : null);
        $this->bag('refund')->set('principal.filter', $filtered);

        return $this->page(1);
    }

    /**
     * @di $validator
     */
    public function createRefund(string $debtId)
    {
        if($this->session->closed)
        {
            $this->notify->warning(trans('meeting.warnings.session.closed'));
            return $this->response;
        }

        $this->validator->validate($debtId);

        $this->refundService->createRefund($this->session, $debtId);

        // Refresh the loans page
        $this->cl(Loan::class)->show($this->session);

        return $this->page();
    }

    public function deleteRefund(int $debtId)
    {
        if($this->session->closed)
        {
            $this->notify->warning(trans('meeting.warnings.session.closed'));
            return $this->response;
        }

        $this->refundService->deleteRefund($this->session, $debtId);

        // Refresh the loans page
        $this->cl(Loan::class)->show($this->session);

        return $this->page();
    }
}
