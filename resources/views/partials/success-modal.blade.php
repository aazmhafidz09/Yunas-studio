@if (session('success_message'))
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div class="text-center">
                    <i class="bx bx-check-circle text-success display-1"></i>
                    <h3 class="mt-4">{{ session('success_title') ?? 'Success!' }}</h3>
                    <p class="text-muted mx-4">{{ session('success_message') }}</p>
                    <div class="mt-4">
                        <a href="{{ session('back_url') ?? url()->previous() }}" class="btn btn-primary w-100">Back to Previous Page</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif