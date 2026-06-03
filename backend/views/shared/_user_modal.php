<?php
use yii\helpers\Json;
use yii\helpers\Url;

$infoUrl = Url::to(['/botuser/info']);
$this->registerJs('window._userInfoUrl = ' . Json::encode($infoUrl) . ';', \yii\web\View::POS_HEAD);

$this->registerJs(<<<'JS'
(function () {
    function fmt(n) {
        return Number(n || 0).toLocaleString('ru-RU');
    }

    function cell(label, value, color) {
        var cls = color ? ' text-' + color : '';
        return '<div class="col-6 mb-2">'
            + '<div class="text-muted small mb-1">' + label + '</div>'
            + '<div class="fw-bold' + cls + '">' + value + '</div>'
            + '</div>';
    }

    function renderUserInfo(d) {
        if (d.error) {
            return '<div class="alert alert-danger mb-0">' + d.error + '</div>';
        }
        return '<div class="row">'
            + cell('Ismi', d.name || '—')
            + cell('Username', d.username ? '@' + d.username : '—')
            + cell('Telefon', d.phone || '—')
            + cell('Balans', fmt(d.balance) + " so'm", 'success')
            + '</div>'
            + '<hr class="my-2">'
            + '<div class="row">'
            + cell('Jami sug\'urtalar', d.police_count + ' ta')
            + cell('To\'langan sug\'urtalar', d.paid_police_count + ' ta', 'success')
            + '</div>'
            + '<hr class="my-2">'
            + '<div class="row">'
            + cell('Jami pul chiqarishlar', d.payment_count + ' ta')
            + cell('Muvaffaqiyatli', d.success_payment_count + ' ta', 'success')
            + cell('Jami chiqarilgan', fmt(d.total_withdrawn) + " so'm", 'danger')
            + cell('Botga qo\'shilgan', d.joined_at || '—')
            + '</div>';
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('.user-info-link');
        if (!link) return;
        e.preventDefault();

        var userId = link.dataset.userId;
        var body   = document.getElementById('userInfoModalBody');
        var modal  = new bootstrap.Modal(document.getElementById('userInfoModal'));

        body.innerHTML = '<div class="text-center py-4">'
            + '<div class="spinner-border text-primary" role="status">'
            + '<span class="visually-hidden">Yuklanmoqda...</span>'
            + '</div></div>';
        modal.show();

        fetch(window._userInfoUrl + '?id=' + userId)
            .then(function (r) { return r.json(); })
            .then(function (data) { body.innerHTML = renderUserInfo(data); })
            .catch(function () {
                body.innerHTML = '<div class="alert alert-danger mb-0">So\'rov xatosi</div>';
            });
    });
})();
JS, \yii\web\View::POS_END);
?>

<div class="modal modal-blur fade" id="userInfoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-user-circle me-2 text-primary"></i>Foydalanuvchi ma'lumotlari
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userInfoModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Yopish
                </button>
            </div>
        </div>
    </div>
</div>
