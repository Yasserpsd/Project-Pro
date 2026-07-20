(function() {
    var modal = document.getElementById('contactModal');
    var pdfViewer = document.getElementById('pdfViewer');
    var pdfFrame = document.getElementById('pdfFrame');
    var pdfDownload = document.getElementById('pdfDownload');
    
    var openModal = document.getElementById('openModal');
    var closeModal = document.getElementById('closeModal');
    var openPdf = document.getElementById('openPdf');
    var closePdf = document.getElementById('closePdf');
    
    if (openModal) {
        openModal.onclick = function() {
            modal.className = 'pd-modal active';
            document.body.style.overflow = 'hidden';
        };
    }
    
    if (closeModal) {
        closeModal.onclick = function() {
            modal.className = 'pd-modal';
            document.body.style.overflow = '';
        };
    }
    
    if (modal) {
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.className = 'pd-modal';
                document.body.style.overflow = '';
            }
        };
    }
    
    if (openPdf) {
        openPdf.onclick = function() {
            var url = this.getAttribute('data-url');
            pdfFrame.src = url;
            pdfDownload.href = url;
            pdfViewer.className = 'pd-pdf active';
            document.body.style.overflow = 'hidden';
        };
    }
    
    if (closePdf) {
        closePdf.onclick = function() {
            pdfViewer.className = 'pd-pdf';
            pdfFrame.src = '';
            document.body.style.overflow = '';
        };
    }
    
    document.onkeydown = function(e) {
        if (e.key === 'Escape') {
            if (modal) {
                modal.className = 'pd-modal';
            }
            if (pdfViewer) {
                pdfViewer.className = 'pd-pdf';
                pdfFrame.src = '';
            }
            document.body.style.overflow = '';
        }
    };
})();
