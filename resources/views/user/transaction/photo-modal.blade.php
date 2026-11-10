<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Photo Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalPhoto" src="" class="img-fluid w-100" style="max-height: 70vh; object-fit: contain;">
                <input type="hidden" id="currentPhotoUrl" value="">
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-primary" onclick="selectCurrentPhoto()">
                    <i class="fas fa-check me-1"></i> Toggle Selection
                </button>
            </div>
        </div>
    </div>
</div>
