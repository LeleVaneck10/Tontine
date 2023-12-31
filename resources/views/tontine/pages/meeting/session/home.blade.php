          <div class="section-body">
            <div class="row align-items-center">
              <div class="col-auto">
                <h2 class="section-title">{{ $session->title }}</h2>
              </div>
              <div class="col">
@include('tontine.pages.meeting.session.action', ['session' => $session])
@include('tontine.pages.meeting.session.open', ['session' => $session])
              </div>
            </div>
          </div>

          <div class="card shadow mb-4">
            <div class="card-body" id="content-page">
              <div class="row mb-2">
                <div class="col">
                  <ul class="nav nav-pills nav-fill" id="session-tabs">
                    <li class="nav-item" role="presentation">
                      <a class="nav-link active" id="session-tab-pools" data-target="#session-pools" href="javascript:void(0)">{{ __('meeting.actions.pools') }}</a>
                    </li>
@if ($tontine->is_financial)
                    <li class="nav-item" role="presentation">
                      <a class="nav-link" id="session-tab-credits" data-target="#session-credits" href="javascript:void(0)">{{ __('meeting.actions.credits') }}</a>
                    </li>
@endif
                    <li class="nav-item" role="presentation">
                      <a class="nav-link" id="session-tab-charges" data-target="#session-charges" href="javascript:void(0)">{{ __('meeting.actions.charges') }}</a>
                    </li>
                    <li class="nav-item" role="presentation">
                      <a class="nav-link" id="session-tab-reports" data-target="#session-reports" href="javascript:void(0)">{{ __('meeting.actions.reports') }}</a>
                    </li>
                  </nav>
                </div>
              </div>
              <div class="row">
                <div class="col">
                  <div class="tab-content" id="session-tabs-content">
                    <div class="tab-pane fade show active" id="session-pools" role="tabpanel" aria-labelledby="session-tab-pools">
                      <div class="row">
                        <div class="col-md-6 col-sm-12" id="meeting-deposits">
                        </div>
                        <div class="col-md-6 col-sm-12" id="meeting-remitments">
                        </div>
                      </div>
                    </div>
@if ($tontine->is_financial)
                    <div class="tab-pane fade" id="session-credits" role="tabpanel" aria-labelledby="session-tab-credits">
                      <div class="row">
                        <div class="col-md-6 col-sm-12" id="meeting-fundings">
                        </div>
                        <div class="col-md-6 col-sm-12" id="meeting-loans">
                        </div>
                        <div class="col-md-6 col-sm-12" id="meeting-principal-refunds">
                        </div>
                        <div class="col-md-6 col-sm-12" id="meeting-interest-refunds">
                        </div>
                      </div>
                    </div>
@endif
                    <div class="tab-pane fade" id="session-charges" role="tabpanel" aria-labelledby="session-tab-charges">
                      <div class="row">
                        <div class="col-md-6 col-sm-12" id="meeting-fees">
                        </div>
                        <div class="col-md-6 col-sm-12" id="meeting-fines">
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane fade" id="session-reports" role="tabpanel" aria-labelledby="session-tab-reports">
                      <div class="row">
                        <div class="col-md-6 col-sm-12">
@include('tontine.pages.meeting.session.agenda', ['session' => $session])
                        </div>
                        <div class="col-md-6 col-sm-12">
@include('tontine.pages.meeting.session.report', ['session' => $session])
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
