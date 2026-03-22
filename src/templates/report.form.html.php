<div class="container">
    <div class="row">
        <div class="col-md-6">
            <h2>Report abuse</h2>
            <p>If you want to report abuse, please fill out the form below. You can report multiple URLs at, one per line and you can add a note.</p>
            <form action="/report" method="post">
                <div class="mb-3">
                    <label for="reporturls" class="form-label">URLs to report</label>
                    <textarea class="form-control" id="reporturls" name="urls" rows="3" placeholder="eg: <?=URL?>fgwdsxxf.jpg"></textarea>
                </div>

                <div class="mb-3">
                    <label for="reportNote" class="form-label">Report Note</label>
                    <textarea class="form-control" id="reportNote" name="note" rows="3" placeholder="Add a note (optional)"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Submit Report</button>
            </form>
        </div>
    </div>
</div>
