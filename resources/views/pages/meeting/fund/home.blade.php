                  <div class="row align-items-center">
                    <div class="col-auto">
                      <div class="section-title mt-0">{!! __('meeting.titles.funds') !!}</div>
                    </div>
@if($session->opened)
                    <div class="col">
                      <div class="btn-group float-right ml-2 mb-2" role="group" aria-label="">
                        <button type="button" class="btn btn-primary" id="btn-funds-refresh"><i class="fa fa-sync"></i></button>
                      </div>
                    </div>
@endif
                  </div>
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>{!! __('common.labels.title') !!}</th>
                          <th>&nbsp;</th>
                          <th>&nbsp;</th>
                        </tr>
                      </thead>
                      <tbody>
@foreach($funds as $fund)
@if($session->disabled($fund))
                        <tr style="background-color:rgba(0, 0, 0, 0.02)">
                          <td>{{ $fund->title }} ({{ $fund->money('amount') }})</td>
                          <td>[{{ $fund->recv_paid }}/{{ $fund->recv_count }}] [{{ $fund->pay_paid }}/{{ $fund->pay_count }}]</td>
                          <td></td>
                        </tr>
@else
                        <tr>
                          <td>{{ $fund->title }} ({{ $fund->money('amount') }})</td>
                          <td>[{{ $fund->recv_paid }}/{{ $fund->recv_count }}] [{{ $fund->pay_paid }}/{{ $fund->pay_count }}]</td>
                          <td class="table-item-menu">
@if($session->opened)
@include('parts.table.menu', [
  'dataIdKey' => 'data-fund-id',
  'dataIdValue' => $fund->id,
  'menus' => [[
    'class' => 'btn-fund-deposits',
    'text' => __('meeting.actions.deposits'),
  ],[
    'class' => $tontine->is_mutual ? 'btn-mutual-remittances' : 'btn-financial-remittances',
    'text' => __('meeting.actions.remittances'),
  ]],
])
@endif
                          </td>
                        </tr>
@endif
@endforeach
                      </tbody>
                    </table>
                  </div> <!-- End table -->
