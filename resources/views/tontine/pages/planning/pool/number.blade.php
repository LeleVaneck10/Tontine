      <div class="portlet-body form">
        <form class="form-horizontal" role="form">
          <div class="module-body">
            <div class="form-group row">
              {!! Form::label('number', trans('number.labels.pool'), ['class' => 'col-md-8 col-form-label']) !!}
              <div class="col-md-4">
                {!! Form::text('number', '', ['class' => 'form-control', 'id' => 'text-number']) !!}
              </div>
            </div>
          </div>
        </form>
      </div>
