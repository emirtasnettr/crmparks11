import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import ApexCharts from 'apexcharts';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

Alpine.plugin(collapse);

window.Alpine = Alpine;

const lockedPresetId = (preset, key) => (preset?.[key] ? String(preset[key]) : '');

const requireEntityId = (errors, field, lockedId, value, message) => {
    if (!lockedId && !value) {
        errors[field] = message;
    }
};

window.formatMoneyExcludingVat = (amount, decimals = 2) => {
    const formatted = new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(amount || 0);

    return `${formatted} ₺ KDV hariç`;
};

const formatMoneyExclVat = (amount, decimals = 2) => window.formatMoneyExcludingVat(amount, decimals);

Alpine.data('themeToggle', () => ({
    theme: localStorage.getItem('theme') || 'system',

    init() {
        this.apply();
    },

    toggle() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', this.theme);
        this.apply();
    },

    apply() {
        const isDark = this.theme === 'dark'
            || (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', isDark);
    },
}));

Alpine.data('sidebar', (initialExpanded = {}) => ({
    open: window.innerWidth >= 1024,
    expanded: initialExpanded,
    toast: null,

    toggle() {
        this.open = !this.open;
    },

    handleCrmlogAction(detail) {
        if (detail?.confirm && ! window.confirm(detail.confirm)) {
            return;
        }

        this.toast = detail?.message || `${detail?.label ?? 'İşlem'} tamamlandı.`;

        window.setTimeout(() => {
            this.toast = null;
        }, 3500);
    },
}));

window.CRMLogRowActionMixin = {
    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
};

Alpine.data('actionMenu', () => ({
    open: false,
    menuStyle: 'opacity:0;pointer-events:none;',
    outsideListener: null,

    init() {
        this._closeOthers = () => {
            if (this.open) {
                this.close();
            }
        };

        this._onScroll = () => {
            if (this.open) {
                this.updatePosition();
            }
        };

        window.addEventListener('crmlog:action-menu-close', this._closeOthers);
        window.addEventListener('scroll', this._onScroll, true);
        window.addEventListener('resize', this._onScroll);
    },

    destroy() {
        this.unbindOutsideClick();
        window.removeEventListener('crmlog:action-menu-close', this._closeOthers);
        window.removeEventListener('scroll', this._onScroll, true);
        window.removeEventListener('resize', this._onScroll);
    },

    toggle() {
        if (this.open) {
            this.close();

            return;
        }

        window.dispatchEvent(new CustomEvent('crmlog:action-menu-close'));

        this.open = true;
        this.menuStyle = 'opacity:0;pointer-events:none;';

        this.$nextTick(() => {
            this.updatePosition();

            requestAnimationFrame(() => {
                this.updatePosition();
                this.bindOutsideClick();
            });
        });
    },

    close() {
        this.open = false;
        this.menuStyle = 'opacity:0;pointer-events:none;';
        this.unbindOutsideClick();
    },

    bindOutsideClick() {
        this.unbindOutsideClick();

        this.outsideListener = (event) => {
            if (!this.open) {
                return;
            }

            const trigger = this.$refs.trigger;
            const menu = this.$refs.menu;

            if (trigger?.contains(event.target) || menu?.contains(event.target)) {
                return;
            }

            this.close();
        };

        // Açılış tıklaması tamamlandıktan sonra dinlemeye başla.
        setTimeout(() => {
            if (!this.open || this.outsideListener === null) {
                return;
            }

            document.addEventListener('click', this.outsideListener, true);
        }, 0);
    },

    unbindOutsideClick() {
        if (this.outsideListener) {
            document.removeEventListener('click', this.outsideListener, true);
            this.outsideListener = null;
        }
    },

    updatePosition() {
        const trigger = this.$refs.trigger?.getBoundingClientRect();
        const menu = this.$refs.menu;

        if (!trigger || !menu) {
            return;
        }

        const menuWidth = menu.offsetWidth || menu.getBoundingClientRect().width || 192;
        const menuHeight = menu.offsetHeight || menu.getBoundingClientRect().height || 120;
        const padding = 8;
        const gap = 6;

        let top = trigger.bottom + gap;
        let left = trigger.right - menuWidth;

        if (top + menuHeight > window.innerHeight - padding) {
            top = trigger.top - menuHeight - gap;
        }

        if (top < padding) {
            top = padding;
        }

        if (left < padding) {
            left = trigger.left;
        }

        if (left + menuWidth > window.innerWidth - padding) {
            left = window.innerWidth - menuWidth - padding;
        }

        this.menuStyle = `top:${Math.round(top)}px;left:${Math.round(left)}px;opacity:1;pointer-events:auto;`;
    },
}));

Alpine.data('businessListPage', (recordsMap = {}) => ({
    recordsMap,
    openDetailModal: false,
    selected: null,

    openDetail(detail) {
        const id = detail?.id;
        this.selected = this.recordsMap[id] ?? this.recordsMap[String(id)] ?? null;
        this.openDetailModal = this.selected !== null;
    },

    closeDetailModal() {
        this.openDetailModal = false;
        this.selected = null;
    },
}));

Alpine.data('agencyListPage', (recordsMap = {}) => ({
    recordsMap,
    openDetailModal: false,
    selected: null,

    openDetail(detail) {
        const id = detail?.id;
        this.selected = this.recordsMap[id] ?? this.recordsMap[String(id)] ?? null;
        this.openDetailModal = this.selected !== null;
    },

    closeDetailModal() {
        this.openDetailModal = false;
        this.selected = null;
    },
}));

Alpine.data('courierListPage', (recordsMap = {}) => ({
    recordsMap,
    openDetailModal: false,
    selected: null,

    openDetail(detail) {
        const id = detail?.id;
        this.selected = this.recordsMap[id] ?? this.recordsMap[String(id)] ?? null;
        this.openDetailModal = this.selected !== null;
    },

    closeDetailModal() {
        this.openDetailModal = false;
        this.selected = null;
    },
}));

Alpine.data('businessForm', (districtsByCity = {}, initial = {}, isEdit = false, earningsEnabled = true) => ({
    districtsByCity,
    districts: [],
    errors: {},
    submitted: false,
    submitting: false,
    validated: false,
    isEdit,
    earningsEnabled,
    form: {
        company_name: '',
        brand_name: '',
        phone: '',
        email: '',
        website: '',
        tax_office: '',
        tax_number: '',
        city: '',
        district: '',
        address: '',
        pricing_model: 'per_package',
        customer_price: '',
        courier_price: '',
        earning_period: '',
        status: 'active',
        notes: '',
        ...initial,
    },

    init() {
        if (this.form.city) {
            this.districts = this.districtsByCity[this.form.city] || [];
        }

        this.$watch('form.pricing_model', () => {
            if (!this.isEdit) {
                this.form.customer_price = '';
                this.form.courier_price = '';
            }
        });
    },

    onCityChange() {
        this.form.district = '';
        this.districts = this.districtsByCity[this.form.city] || [];
    },

    validate() {
        this.errors = {};

        if (!this.form.company_name.trim()) {
            this.errors.company_name = 'Firma ünvanı zorunludur.';
        }

        if (!this.form.phone.trim()) {
            this.errors.phone = 'Telefon numarası zorunludur.';
        }

        if (!this.form.pricing_model) {
            this.errors.pricing_model = 'Çalışma modeli seçilmelidir.';
        }

        if (this.earningsEnabled && !this.form.earning_period) {
            this.errors.earning_period = 'Hakediş periyodu seçilmelidir.';
        }

        return Object.keys(this.errors).length === 0;
    },

    submit() {
        this.submitted = false;

        if (!this.validate()) {
            const firstError = this.$el.querySelector('[x-text*="errors."]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        this.submitted = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    handleSubmit(event) {
        if (this.validated) {
            return;
        }

        event.preventDefault();

        if (!this.validate()) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        this.validated = true;
        this.submitting = true;
        event.target.submit();
    },
}));

Alpine.data('agencyForm', (districtsByCity = {}, initial = {}, isEdit = false) => ({
    districtsByCity,
    districts: [],
    errors: {},
    submitted: false,
    submitting: false,
    validated: false,
    isEdit,
    form: {
        company_name: '',
        brand_name: '',
        phone: '',
        email: '',
        website: '',
        tax_office: '',
        tax_number: '',
        mersis_number: '',
        trade_registry_number: '',
        city: '',
        district: '',
        address: '',
        commission_rate: '',
        payment_period: '',
        bank_key: '',
        account_holder: '',
        iban: '',
        status: 'active',
        notes: '',
        ...initial,
    },

    init() {
        if (this.form.city) {
            this.districts = this.districtsByCity[this.form.city] || [];
        }

        if (this.form.iban) {
            this.formatIbanInput();
        }
    },

    onCityChange() {
        this.form.district = '';
        this.districts = this.districtsByCity[this.form.city] || [];
    },

    formatIbanInput() {
        let value = (this.form.iban || '').toUpperCase().replace(/[^A-Z0-9]/g, '');

        if (value.length > 0 && !value.startsWith('TR')) {
            value = 'TR' + value.replace(/^TR/, '');
        }

        if (value.length > 26) {
            value = value.slice(0, 26);
        }

        this.form.iban = value.replace(/(.{4})/g, '$1 ').trim();
    },

    validate() {
        this.errors = {};

        if (!this.form.company_name.trim()) {
            this.errors.company_name = 'Firma ünvanı zorunludur.';
        }

        if (!this.form.phone.trim()) {
            this.errors.phone = 'Telefon zorunludur.';
        }

        if (!this.form.tax_number.trim()) {
            this.errors.tax_number = 'Vergi numarası zorunludur.';
        }

        if (!this.form.city) {
            this.errors.city = 'İl seçilmelidir.';
        }

        if (!this.form.district) {
            this.errors.district = 'İlçe seçilmelidir.';
        }

        if (!this.form.address.trim()) {
            this.errors.address = 'Açık adres zorunludur.';
        }

        return Object.keys(this.errors).length === 0;
    },

    submit() {
        this.submitted = false;

        if (!this.validate()) {
            const firstError = this.$el.querySelector('[x-text*="errors."]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        this.submitted = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    handleSubmit(event) {
        if (this.validated) {
            return;
        }

        event.preventDefault();

        if (!this.validate()) {
            const firstError = this.$el.querySelector('[x-text*="errors."]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        this.validated = true;
        this.submitting = true;
        event.target.submit();
    },
}));

Alpine.data('contactPage', (preset = {}) => {
    const lockedBusinessId = lockedPresetId(preset, 'businessId');

    return {
    lockedBusinessId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    modal: {
        business_id: lockedBusinessId,
        full_name: '',
        title: '',
        phone: '',
        email: '',
        is_default: false,
        status: 'active',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.submitting = false;
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            business_id: this.lockedBusinessId,
            full_name: '',
            title: '',
            phone: '',
            email: '',
            is_default: false,
            status: 'active',
        };
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'business_id', this.lockedBusinessId, this.modal.business_id, 'İşletme seçilmelidir.');

        if (!this.modal.full_name.trim()) {
            this.modalErrors.full_name = 'Ad soyad zorunludur.';
        }

        if (!this.modal.title) {
            this.modalErrors.title = 'Görev seçilmelidir.';
        }

        if (!this.modal.phone.trim()) {
            this.modalErrors.phone = 'Telefon zorunludur.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('agencyContactPage', (preset = {}) => {
    const lockedAgencyId = lockedPresetId(preset, 'agencyId');

    return {
    lockedAgencyId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    modal: {
        agency_id: lockedAgencyId,
        first_name: '',
        last_name: '',
        title: '',
        phone: '',
        email: '',
        is_default: false,
        status: 'active',
        notes: '',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.submitting = false;
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            agency_id: this.lockedAgencyId,
            first_name: '',
            last_name: '',
            title: '',
            phone: '',
            email: '',
            is_default: false,
            status: 'active',
            notes: '',
        };
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'agency_id', this.lockedAgencyId, this.modal.agency_id, 'Acente seçilmelidir.');

        if (!this.modal.first_name.trim()) {
            this.modalErrors.first_name = 'Ad zorunludur.';
        }

        if (!this.modal.last_name.trim()) {
            this.modalErrors.last_name = 'Soyad zorunludur.';
        }

        if (!this.modal.title) {
            this.modalErrors.title = 'Görev seçilmelidir.';
        }

        if (!this.modal.phone.trim()) {
            this.modalErrors.phone = 'Telefon zorunludur.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('agencyCourierPage', (preset = {}) => {
    const lockedAgencyId = lockedPresetId(preset, 'agencyId');

    return {
    lockedAgencyId,
    openAssignModal: false,
    openDetailModal: false,
    assignErrors: {},
    assignSubmitting: false,
    selected: null,
    assignModal: {
        agency_id: lockedAgencyId,
        courier_id: '',
        start_date: '',
        end_date: '',
        status: 'active',
        notes: '',
    },

    closeAssignModal() {
        this.openAssignModal = false;
        this.assignErrors = {};
        this.assignSubmitting = false;
        this.resetAssignModal();
    },

    resetAssignModal() {
        this.assignModal = {
            agency_id: this.lockedAgencyId,
            courier_id: '',
            start_date: '',
            end_date: '',
            status: 'active',
            notes: '',
        };
    },

    validateAssignModal() {
        this.assignErrors = {};

        requireEntityId(this.assignErrors, 'agency_id', this.lockedAgencyId, this.assignModal.agency_id, 'Acente seçilmelidir.');

        if (!this.assignModal.courier_id) {
            this.assignErrors.courier_id = 'Kurye seçilmelidir.';
        }

        if (!this.assignModal.start_date) {
            this.assignErrors.start_date = 'Başlangıç tarihi zorunludur.';
        }

        if (this.assignModal.start_date && this.assignModal.end_date && this.assignModal.end_date < this.assignModal.start_date) {
            this.assignErrors.start_date = 'Bitiş tarihi başlangıçtan önce olamaz.';
        }

        return Object.keys(this.assignErrors).length === 0;
    },

    handleAssignSubmit(event) {
        if (!this.validateAssignModal()) {
            event.preventDefault();
            return;
        }

        this.assignSubmitting = true;
    },

    openDetail(record) {
        this.selected = record;
        this.openDetailModal = true;
    },

    closeDetailModal() {
        this.openDetailModal = false;
        this.selected = null;
    },
};
});

Alpine.data('contractPage', (preset = {}) => {
    const lockedBusinessId = lockedPresetId(preset, 'businessId');

    return {
    lockedBusinessId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    modal: {
        business_id: lockedBusinessId,
        contract_number: '',
        contract_type: '',
        start_date: '',
        end_date: '',
        notes: '',
        status: 'draft',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            business_id: this.lockedBusinessId,
            contract_number: '',
            contract_type: '',
            start_date: '',
            end_date: '',
            notes: '',
            status: 'draft',
        };
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'business_id', this.lockedBusinessId, this.modal.business_id, 'İşletme seçilmelidir.');

        if (!this.modal.contract_type) {
            this.modalErrors.contract_type = 'Sözleşme türü seçilmelidir.';
        }

        if (!this.modal.start_date) {
            this.modalErrors.start_date = 'Başlangıç tarihi zorunludur.';
        }

        if (!this.modal.end_date) {
            this.modalErrors.end_date = 'Bitiş tarihi zorunludur.';
        }

        if (this.modal.start_date && this.modal.end_date && this.modal.end_date < this.modal.start_date) {
            this.modalErrors.end_date = 'Bitiş tarihi başlangıçtan önce olamaz.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('agencyContractPage', (preset = {}) => {
    const lockedAgencyId = lockedPresetId(preset, 'agencyId');

    return {
    lockedAgencyId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    modal: {
        agency_id: lockedAgencyId,
        contract_number: '',
        contract_type: '',
        start_date: '',
        end_date: '',
        auto_renewal: false,
        notes: '',
        status: 'draft',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            agency_id: this.lockedAgencyId,
            contract_number: '',
            contract_type: '',
            start_date: '',
            end_date: '',
            auto_renewal: false,
            notes: '',
            status: 'draft',
        };
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'agency_id', this.lockedAgencyId, this.modal.agency_id, 'Acente seçilmelidir.');

        if (!this.modal.contract_type) {
            this.modalErrors.contract_type = 'Sözleşme türü seçilmelidir.';
        }

        if (!this.modal.start_date) {
            this.modalErrors.start_date = 'Başlangıç tarihi zorunludur.';
        }

        if (!this.modal.end_date) {
            this.modalErrors.end_date = 'Bitiş tarihi zorunludur.';
        }

        if (this.modal.start_date && this.modal.end_date && this.modal.end_date < this.modal.start_date) {
            this.modalErrors.end_date = 'Bitiş tarihi başlangıçtan önce olamaz.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('agencyEarningPage', () => ({
    activeModal: null,
    singleSaved: false,
    bulkSaved: false,
    singleErrors: {},
    single: {
        agency_id: '',
        period_month: '',
        period_year: '',
        courier_count: '',
        package_count: '',
        gross_amount: '',
        extra_payment: 0,
        deduction: 0,
        description: '',
        status: 'draft',
    },

    closeModals() {
        this.activeModal = null;
        this.singleSaved = false;
        this.bulkSaved = false;
        this.singleErrors = {};
        this.resetSingle();
    },

    resetSingle() {
        this.single = {
            agency_id: '',
            period_month: '',
            period_year: '',
            courier_count: '',
            package_count: '',
            gross_amount: '',
            extra_payment: 0,
            deduction: 0,
            description: '',
            status: 'draft',
        };
    },

    calcNet() {
        const gross = parseFloat(this.single.gross_amount) || 0;
        const extra = parseFloat(this.single.extra_payment) || 0;
        const deduction = parseFloat(this.single.deduction) || 0;
        const net = gross + extra - deduction;

        return { net };
    },

    formatMoney(amount) {
        return formatMoneyExclVat(amount);
    },

    validateSingle() {
        this.singleErrors = {};

        if (!this.single.agency_id) {
            this.singleErrors.agency_id = 'Acente seçilmelidir.';
        }

        if (!this.single.period_month) {
            this.singleErrors.period_month = 'Ay seçilmelidir.';
        }

        if (!this.single.period_year) {
            this.singleErrors.period_year = 'Yıl seçilmelidir.';
        }

        return Object.keys(this.singleErrors).length === 0;
    },

    saveSingle() {
        this.singleSaved = false;

        if (!this.validateSingle()) {
            return;
        }

        this.singleSaved = true;
    },

    importBulk() {
        this.bulkSaved = true;
    },
}));

Alpine.data('agencyDocumentPage', (preset = {}) => {
    const lockedAgencyId = lockedPresetId(preset, 'agencyId');

    return {
    lockedAgencyId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    selectedFileName: '',
    modal: {
        agency_id: lockedAgencyId,
        document_type: '',
        expires_at: '',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.selectedFileName = '';
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            agency_id: this.lockedAgencyId,
            document_type: '',
            expires_at: '',
        };
    },

    onFileSelect(event) {
        const file = event.target.files[0];
        this.selectedFileName = file ? file.name : '';
        this.modalErrors.file = null;
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'agency_id', this.lockedAgencyId, this.modal.agency_id, 'Acente seçilmelidir.');

        if (!this.modal.document_type) {
            this.modalErrors.document_type = 'Evrak türü seçilmelidir.';
        }

        if (!this.selectedFileName) {
            this.modalErrors.file = 'Dosya seçilmelidir.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('assignmentPage', (preset = {}) => {
    const lockedBusinessId = lockedPresetId(preset, 'businessId');

    return {
    lockedBusinessId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    modal: {
        business_id: lockedBusinessId,
        courier_id: '',
        courier_type: 'independent',
        agency_id: '',
        start_date: '',
        end_date: '',
        notes: '',
        status: 'active',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            business_id: this.lockedBusinessId,
            courier_id: '',
            courier_type: 'independent',
            agency_id: '',
            start_date: '',
            end_date: '',
            notes: '',
            status: 'active',
        };
    },

    onCourierChange(event) {
        const option = event.target.selectedOptions[0];
        if (!option || !option.value) {
            return;
        }

        this.modal.courier_type = option.dataset.type || 'independent';
        this.modal.agency_id = option.dataset.agency || '';
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'business_id', this.lockedBusinessId, this.modal.business_id, 'İşletme seçilmelidir.');

        if (!this.modal.courier_id) {
            this.modalErrors.courier_id = 'Kurye seçilmelidir.';
        }

        if (!this.modal.start_date) {
            this.modalErrors.start_date = 'Başlangıç tarihi zorunludur.';
        }

        if (this.modal.start_date && this.modal.end_date && this.modal.end_date < this.modal.start_date) {
            this.modalErrors.start_date = 'Bitiş tarihi başlangıçtan önce olamaz.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('earningPage', () => ({
    activeModal: null,
    submitting: false,
    bulkSaved: false,
    singleErrors: {},
    single: {
        business_id: '',
        courier_id: '',
        period_month: '',
        period_year: '',
        pricing_model: 'per_package',
        package_count: '',
        revenue_unit_price: '',
        courier_unit_price: '',
        revenue_total: '',
        courier_payment: '',
        extra_income: 0,
        extra_expense: 0,
        deduction: 0,
        description: '',
    },

    closeModals() {
        this.activeModal = null;
        this.submitting = false;
        this.bulkSaved = false;
        this.singleErrors = {};
        this.resetSingle();
    },

    resetSingle() {
        this.single = {
            business_id: '',
            courier_id: '',
            period_month: '',
            period_year: '',
            pricing_model: 'per_package',
            package_count: '',
            revenue_unit_price: '',
            courier_unit_price: '',
            revenue_total: '',
            courier_payment: '',
            extra_income: 0,
            extra_expense: 0,
            deduction: 0,
            description: '',
        };
    },

    calcSingle() {
        const s = this.single;
        let revenue = 0;
        let courier = 0;

        if (s.pricing_model === 'per_package') {
            revenue = (parseFloat(s.package_count) || 0) * (parseFloat(s.revenue_unit_price) || 0);
            courier = (parseFloat(s.package_count) || 0) * (parseFloat(s.courier_unit_price) || 0);
        } else {
            revenue = parseFloat(s.revenue_total) || 0;
            courier = parseFloat(s.courier_payment) || 0;
        }

        const extraIncome = parseFloat(s.extra_income) || 0;
        const extraExpense = parseFloat(s.extra_expense) || 0;
        const deduction = parseFloat(s.deduction) || 0;
        const expense = courier + extraExpense;
        const profit = revenue - courier - extraExpense + extraIncome - deduction;

        return { revenue, expense, profit };
    },

    formatMoney(amount) {
        return formatMoneyExclVat(amount);
    },

    validateSingle() {
        this.singleErrors = {};

        if (!this.single.business_id) this.singleErrors.business_id = 'İşletme seçilmelidir.';
        if (!this.single.courier_id) this.singleErrors.courier_id = 'Kurye seçilmelidir.';
        if (!this.single.period_month) this.singleErrors.period_month = 'Ay seçilmelidir.';
        if (!this.single.period_year) this.singleErrors.period_year = 'Yıl seçilmelidir.';

        return Object.keys(this.singleErrors).length === 0;
    },

    handleSingleSubmit(event) {
        if (!this.validateSingle()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },

    importBulk() {
        this.bulkSaved = true;
    },
}));

Alpine.data('courierEarningPage', () => ({
    activeModal: null,
    singleSaved: false,
    bulkSaved: false,
    singleErrors: {},
    single: {
        courier_id: '',
        business_id: '',
        period_month: '',
        period_year: '',
        package_count: '',
        unit_price: '',
        earning_amount: '',
        extra_payment: 0,
        deduction: 0,
        payment_status: 'pending',
        description: '',
    },

    closeModals() {
        this.activeModal = null;
        this.singleSaved = false;
        this.bulkSaved = false;
        this.singleErrors = {};
        this.resetSingle();
    },

    resetSingle() {
        this.single = {
            courier_id: '',
            business_id: '',
            period_month: '',
            period_year: '',
            package_count: '',
            unit_price: '',
            earning_amount: '',
            extra_payment: 0,
            deduction: 0,
            payment_status: 'pending',
            description: '',
        };
    },

    updateEarningAmount() {
        const packages = parseFloat(this.single.package_count) || 0;
        const unitPrice = parseFloat(this.single.unit_price) || 0;

        if (packages > 0 && unitPrice > 0) {
            this.single.earning_amount = (packages * unitPrice).toFixed(2);
        }
    },

    calcNet() {
        const earning = parseFloat(this.single.earning_amount) || 0;
        const extra = parseFloat(this.single.extra_payment) || 0;
        const deduction = parseFloat(this.single.deduction) || 0;
        const net = earning + extra - deduction;

        return { net };
    },

    formatMoney(amount) {
        return formatMoneyExclVat(amount);
    },

    validateSingle() {
        this.singleErrors = {};

        if (!this.single.courier_id) {
            this.singleErrors.courier_id = 'Kurye seçilmelidir.';
        }

        if (!this.single.business_id) {
            this.singleErrors.business_id = 'İşletme seçilmelidir.';
        }

        if (!this.single.period_month) {
            this.singleErrors.period_month = 'Ay seçilmelidir.';
        }

        if (!this.single.period_year) {
            this.singleErrors.period_year = 'Yıl seçilmelidir.';
        }

        return Object.keys(this.singleErrors).length === 0;
    },

    saveSingle() {
        this.singleSaved = false;

        if (!this.validateSingle()) {
            return;
        }

        this.singleSaved = true;
    },

    importBulk() {
        this.bulkSaved = true;
    },
}));

Alpine.data('documentPage', (preset = {}) => {
    const lockedBusinessId = lockedPresetId(preset, 'businessId');

    return {
    lockedBusinessId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    selectedFileName: '',
    modal: {
        business_id: lockedBusinessId,
        document_type: '',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.selectedFileName = '';
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            business_id: this.lockedBusinessId,
            document_type: '',
        };
    },

    onFileSelect(event) {
        const file = event.target.files[0];
        this.selectedFileName = file ? file.name : '';
        this.modalErrors.file = null;
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'business_id', this.lockedBusinessId, this.modal.business_id, 'İşletme seçilmelidir.');

        if (!this.modal.document_type) {
            this.modalErrors.document_type = 'Evrak türü seçilmelidir.';
        }

        if (!this.selectedFileName) {
            this.modalErrors.file = 'Dosya seçilmelidir.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('courierForm', (districtsByCity = {}, initial = {}, isEdit = false) => ({
    districtsByCity,
    districts: [],
    errors: {},
    submitted: false,
    submitting: false,
    validated: false,
    isEdit,
    form: {
        first_name: '',
        last_name: '',
        tc_number: '',
        birth_date: '',
        phone: '',
        email: '',
        courier_type: 'independent',
        agency_id: '',
        tax_office: '',
        tax_number: '',
        company_name: '',
        city: '',
        district: '',
        address: '',
        vehicle_type: '',
        plate: '',
        vehicle_brand: '',
        vehicle_model: '',
        bank_name: '',
        iban: '',
        account_holder: '',
        start_date: '',
        status: 'active',
        notes: '',
        ...initial,
    },

    init() {
        if (this.form.city) {
            this.districts = this.districtsByCity[this.form.city] || [];
        }

        this.$watch('form.courier_type', (value) => {
            if (value === 'independent') {
                this.form.agency_id = '';
            } else if (!this.isEdit) {
                this.form.tax_office = '';
                this.form.tax_number = '';
                this.form.company_name = '';
            }
        });
    },

    onCityChange() {
        this.form.district = '';
        this.districts = this.districtsByCity[this.form.city] || [];
    },

    validate() {
        this.errors = {};

        if (!this.form.first_name.trim()) {
            this.errors.first_name = 'Ad zorunludur.';
        }

        if (!this.form.last_name.trim()) {
            this.errors.last_name = 'Soyad zorunludur.';
        }

        if (!this.form.phone.trim()) {
            this.errors.phone = 'Telefon zorunludur.';
        }

        if (!this.form.courier_type) {
            this.errors.courier_type = 'Kurye tipi seçilmelidir.';
        }

        if (this.form.courier_type === 'agency' && !this.form.agency_id) {
            this.errors.agency_id = 'Bağlı acente seçilmelidir.';
        }

        if (!this.form.vehicle_type) {
            this.errors.vehicle_type = 'Araç tipi seçilmelidir.';
        }

        if (!this.form.start_date) {
            this.errors.start_date = 'Başlangıç tarihi zorunludur.';
        }

        return Object.keys(this.errors).length === 0;
    },

    submit() {
        this.submitted = false;

        if (!this.validate()) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        this.submitted = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    handleSubmit(event) {
        if (this.validated) {
            return;
        }

        event.preventDefault();

        if (!this.validate()) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        this.validated = true;
        this.submitting = true;
        event.target.submit();
    },
}));

Alpine.data('courierDocumentPage', (preset = {}) => {
    const lockedCourierId = lockedPresetId(preset, 'courierId');

    return {
    lockedCourierId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    selectedFileName: '',
    modal: {
        courier_id: lockedCourierId,
        document_type: '',
        document_number: '',
        expiry_date: '',
        description: '',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.selectedFileName = '';
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            courier_id: this.lockedCourierId,
            document_type: '',
            document_number: '',
            expiry_date: '',
            description: '',
        };
    },

    onFileSelect(event) {
        const file = event.target.files[0];
        this.selectedFileName = file ? file.name : '';
        this.modalErrors.file = null;
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'courier_id', this.lockedCourierId, this.modal.courier_id, 'Kurye seçilmelidir.');

        if (!this.modal.document_type) {
            this.modalErrors.document_type = 'Belge türü seçilmelidir.';
        }

        if (!this.selectedFileName) {
            this.modalErrors.file = 'Dosya seçilmelidir.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('courierVehiclePage', (preset = {}) => {
    const lockedCourierId = lockedPresetId(preset, 'courierId');

    return {
    lockedCourierId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    modal: {
        courier_id: lockedCourierId,
        vehicle_type: '',
        plate: '',
        brand: '',
        model: '',
        model_year: '',
        color: '',
        license_number: '',
        insurance_policy_number: '',
        insurance_expiry_date: '',
        status: 'active',
        notes: '',
    },

    get isPedestrian() {
        return this.modal.vehicle_type === 'pedestrian';
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            courier_id: this.lockedCourierId,
            vehicle_type: '',
            plate: '',
            brand: '',
            model: '',
            model_year: '',
            color: '',
            license_number: '',
            insurance_policy_number: '',
            insurance_expiry_date: '',
            status: 'active',
            notes: '',
        };
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'courier_id', this.lockedCourierId, this.modal.courier_id, 'Kurye seçilmelidir.');

        if (!this.modal.vehicle_type) {
            this.modalErrors.vehicle_type = 'Araç tipi seçilmelidir.';
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('courierBankAccountPage', (preset = {}) => {
    const lockedCourierId = lockedPresetId(preset, 'courierId');

    return {
    lockedCourierId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    modal: {
        courier_id: lockedCourierId,
        bank_key: '',
        account_holder: '',
        iban: '',
        branch_code: '',
        account_number: '',
        is_default: false,
        status: 'active',
        notes: '',
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.resetModal();
    },

    resetModal() {
        this.modal = {
            courier_id: this.lockedCourierId,
            bank_key: '',
            account_holder: '',
            iban: '',
            branch_code: '',
            account_number: '',
            is_default: false,
            status: 'active',
            notes: '',
        };
    },

    formatIbanInput() {
        let value = (this.modal.iban || '').toUpperCase().replace(/[^A-Z0-9]/g, '');

        if (value.length > 0 && !value.startsWith('TR')) {
            value = 'TR' + value.replace(/^TR/, '');
        }

        if (value.length > 26) {
            value = value.slice(0, 26);
        }

        this.modal.iban = value.replace(/(.{4})/g, '$1 ').trim();
    },

    validateIban(iban) {
        const clean = (iban || '').replace(/\s+/g, '').toUpperCase();

        if (!clean.startsWith('TR')) {
            return 'IBAN TR ile başlamalıdır.';
        }

        if (clean.length !== 26) {
            return 'IBAN 26 karakter olmalıdır (TR + 24 rakam).';
        }

        if (!/^TR\d{24}$/.test(clean)) {
            return 'IBAN formatı geçersiz.';
        }

        return null;
    },

    validateModal() {
        this.modalErrors = {};

        requireEntityId(this.modalErrors, 'courier_id', this.lockedCourierId, this.modal.courier_id, 'Kurye seçilmelidir.');

        if (!this.modal.bank_key) {
            this.modalErrors.bank_key = 'Banka seçilmelidir.';
        }

        if (!this.modal.account_holder?.trim()) {
            this.modalErrors.account_holder = 'Hesap sahibi zorunludur.';
        }

        const ibanError = this.validateIban(this.modal.iban);
        if (ibanError) {
            this.modalErrors.iban = ibanError;
        }

        return Object.keys(this.modalErrors).length === 0;
    },

    handleSubmit(event) {
        if (!this.validateModal()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
};
});

Alpine.data('courierActivityPage', () => ({
    openModal: false,
    selected: null,

    openDetail(activity) {
        this.selected = activity;
        this.openModal = true;
    },

    closeModal() {
        this.openModal = false;
        this.selected = null;
    },
}));

Alpine.data('agencyActivityPage', () => ({
    openModal: false,
    selected: null,

    openDetail(activity) {
        this.selected = activity;
        this.openModal = true;
    },

    closeModal() {
        this.openModal = false;
        this.selected = null;
    },
}));

Alpine.data('financeDashboardPage', () => ({
    showCustomRange: false,
    customStart: '',
    customEnd: '',
    chartInstances: [],
    themeObserver: null,

    init() {
        this.showCustomRange = this.$el.dataset.period === 'custom';
        this.customStart = new URLSearchParams(window.location.search).get('start_date') || '';
        this.customEnd = new URLSearchParams(window.location.search).get('end_date') || '';

        this.$nextTick(() => this.renderCharts());

        this.themeObserver = new MutationObserver(() => this.renderCharts());
        this.themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    },

    destroy() {
        this.themeObserver?.disconnect();
        this.destroyCharts();
    },

    getChartTheme() {
        const isDark = document.documentElement.classList.contains('dark');

        return {
            isDark,
            textColor: isDark ? '#94a3b8' : '#6b7280',
            gridColor: isDark ? '#334155' : '#e5e7eb',
            tooltipTheme: isDark ? 'dark' : 'light',
        };
    },

    destroyCharts() {
        this.chartInstances.forEach((chart) => chart.destroy());
        this.chartInstances = [];
    },

    renderCharts() {
        const raw = this.$el.dataset.charts;
        if (!raw) {
            return;
        }

        const data = JSON.parse(raw);
        const theme = this.getChartTheme();

        this.destroyCharts();

        const baseChartOptions = {
            chart: {
                background: 'transparent',
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            theme: { mode: theme.isDark ? 'dark' : 'light' },
            grid: {
                borderColor: theme.gridColor,
                strokeDashArray: 4,
            },
            tooltip: { theme: theme.tooltipTheme },
        };

        const revenueExpenseEl = document.querySelector('#finance-chart-revenue-expense');
        if (revenueExpenseEl) {
            const chart = new ApexCharts(revenueExpenseEl, {
                ...baseChartOptions,
                chart: {
                    ...baseChartOptions.chart,
                    type: 'line',
                    height: 280,
                },
                colors: ['#10b981', '#ef4444'],
                stroke: { curve: 'smooth', width: 2 },
                series: [
                    { name: 'Gelir', data: data.revenue_expense.revenue },
                    { name: 'Gider', data: data.revenue_expense.expense },
                ],
                xaxis: {
                    categories: data.months,
                    labels: { style: { colors: theme.textColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000_000).toFixed(1)}M`,
                    },
                },
                legend: {
                    labels: { colors: theme.textColor },
                    position: 'top',
                    horizontalAlign: 'right',
                },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const profitEl = document.querySelector('#finance-chart-profit');
        if (profitEl) {
            const chart = new ApexCharts(profitEl, {
                ...baseChartOptions,
                chart: {
                    ...baseChartOptions.chart,
                    type: 'bar',
                    height: 280,
                },
                colors: ['#6366f1'],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '55%',
                    },
                },
                series: [{ name: 'Net Kâr', data: data.profit }],
                xaxis: {
                    categories: data.months,
                    labels: { style: { colors: theme.textColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000_000).toFixed(1)}M`,
                    },
                },
                dataLabels: { enabled: false },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const revenueDistEl = document.querySelector('#finance-chart-revenue-distribution');
        if (revenueDistEl) {
            const chart = new ApexCharts(revenueDistEl, {
                ...baseChartOptions,
                chart: {
                    ...baseChartOptions.chart,
                    type: 'donut',
                    height: 280,
                },
                colors: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#06b6d4', '#94a3b8'],
                labels: data.revenue_by_business.map((item) => item.label),
                series: data.revenue_by_business.map((item) => item.value),
                legend: {
                    position: 'bottom',
                    labels: { colors: theme.textColor },
                },
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Toplam',
                                    color: theme.textColor,
                                    formatter: (w) => {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return `${(total / 1_000_000).toFixed(1)}M ₺`;
                                    },
                                },
                            },
                        },
                    },
                },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const expenseDistEl = document.querySelector('#finance-chart-expense-distribution');
        if (expenseDistEl) {
            const chart = new ApexCharts(expenseDistEl, {
                ...baseChartOptions,
                chart: {
                    ...baseChartOptions.chart,
                    type: 'donut',
                    height: 280,
                },
                colors: ['#ef4444', '#f59e0b', '#64748b'],
                labels: data.expense_breakdown.map((item) => item.label),
                series: data.expense_breakdown.map((item) => item.value),
                legend: {
                    position: 'bottom',
                    labels: { colors: theme.textColor },
                },
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Toplam',
                                    color: theme.textColor,
                                    formatter: (w) => {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return `${(total / 1_000_000).toFixed(1)}M ₺`;
                                    },
                                },
                            },
                        },
                    },
                },
            });
            chart.render();
            this.chartInstances.push(chart);
        }
    },

    selectPeriod(period) {
        if (period === 'custom') {
            this.showCustomRange = true;
            return;
        }

        window.location.href = `${window.location.pathname}?period=${period}`;
    },

    applyCustomRange() {
        if (!this.customStart || !this.customEnd) {
            return;
        }

        const params = new URLSearchParams({
            period: 'custom',
            start_date: this.customStart,
            end_date: this.customEnd,
        });

        window.location.href = `${window.location.pathname}?${params.toString()}`;
    },
}));

Alpine.data('financeCurrentAccountPage', (accountDetails = {}) => ({
    accountDetails,
    activeModal: null,
    selected: null,
    movementSaved: false,
    newAccountSaved: false,
    movementErrors: {},
    movement: {
        account_id: '',
        transaction_date: '2026-07-07',
        type: '',
        document_no: '',
        amount: '',
        description: '',
    },
    newAccount: {
        type: 'business',
        title: '',
        phone: '',
        email: '',
        tax_number: '',
    },

    openCard(id) {
        this.selected = this.accountDetails[id] ?? this.accountDetails[String(id)] ?? null;
        this.activeModal = 'card';
    },

    openStatement(id) {
        this.selected = this.accountDetails[id] ?? this.accountDetails[String(id)] ?? null;
        this.activeModal = 'statement';
    },

    openMovement(payload = {}) {
        this.resetMovement();
        this.movementSaved = false;
        this.movementErrors = {};

        if (payload?.id) {
            this.movement.account_id = String(payload.id);
        }

        if (payload?.preset) {
            this.movement.type = payload.preset;
        }

        this.activeModal = 'movement';
    },

    openNewAccount() {
        this.newAccountSaved = false;
        this.newAccount = {
            type: 'business',
            title: '',
            phone: '',
            email: '',
            tax_number: '',
        };
        this.activeModal = 'new-account';
    },

    closeModals() {
        this.activeModal = null;
        this.selected = null;
        this.movementSaved = false;
        this.newAccountSaved = false;
        this.movementErrors = {};
    },

    resetMovement() {
        this.movement = {
            account_id: '',
            transaction_date: '2026-07-07',
            type: '',
            document_no: '',
            amount: '',
            description: '',
        };
    },

    validateMovement() {
        this.movementErrors = {};

        if (!this.movement.account_id) {
            this.movementErrors.account_id = 'Cari seçilmelidir.';
        }

        if (!this.movement.type) {
            this.movementErrors.type = 'İşlem türü seçilmelidir.';
        }

        if (!this.movement.amount || Number(this.movement.amount) <= 0) {
            this.movementErrors.amount = 'Geçerli bir tutar girin.';
        }

        return Object.keys(this.movementErrors).length === 0;
    },

    saveMovement() {
        this.movementSaved = false;

        if (!this.validateMovement()) {
            return;
        }

        this.movementSaved = true;
    },

    saveNewAccount() {
        if (!this.newAccount.title?.trim() || !this.newAccount.phone?.trim()) {
            return;
        }

        this.newAccountSaved = true;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
}));

Alpine.data('financeRevenuePage', () => ({
    activeModal: null,
    saved: false,
    errors: {},
    form: {
        business_id: '',
        revenue_type: '',
        period_label: '',
        invoice_no: '',
        revenue_date: '2026-07-07',
        amount: '',
        vat_rate: 20,
        description: '',
        collection_status: 'pending',
    },

    closeModals() {
        this.activeModal = null;
        this.saved = false;
        this.errors = {};
        this.resetForm();
    },

    resetForm() {
        this.form = {
            business_id: '',
            revenue_type: '',
            period_label: '',
            invoice_no: '',
            revenue_date: '2026-07-07',
            amount: '',
            vat_rate: 20,
            description: '',
            collection_status: 'pending',
        };
    },

    validateForm() {
        this.errors = {};

        if (!this.form.business_id) {
            this.errors.business_id = 'İşletme seçilmelidir.';
        }

        if (!this.form.revenue_type) {
            this.errors.revenue_type = 'Gelir türü seçilmelidir.';
        }

        if (!this.form.amount || Number(this.form.amount) <= 0) {
            this.errors.amount = 'Geçerli bir tutar girin.';
        }

        return Object.keys(this.errors).length === 0;
    },

    saveRevenue() {
        this.saved = false;

        if (!this.validateForm()) {
            return;
        }

        this.saved = true;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
}));

Alpine.data('financeExpensePage', () => ({
    activeModal: null,
    saved: false,
    errors: {},
    form: {
        expense_type: '',
        courier_id: '',
        agency_id: '',
        expense_date: '2026-07-07',
        amount: '',
        vat_rate: 20,
        description: '',
        payment_status: 'pending',
        document_no: '',
    },

    closeModals() {
        this.activeModal = null;
        this.saved = false;
        this.errors = {};
        this.resetForm();
    },

    resetForm() {
        this.form = {
            expense_type: '',
            courier_id: '',
            agency_id: '',
            expense_date: '2026-07-07',
            amount: '',
            vat_rate: 20,
            description: '',
            payment_status: 'pending',
            document_no: '',
        };
    },

    validateForm() {
        this.errors = {};

        if (!this.form.expense_type) {
            this.errors.expense_type = 'Gider türü seçilmelidir.';
        }

        if (!this.form.expense_date) {
            this.errors.expense_date = 'Gider tarihi zorunludur.';
        }

        if (!this.form.amount || Number(this.form.amount) <= 0) {
            this.errors.amount = 'Geçerli bir tutar girin.';
        }

        return Object.keys(this.errors).length === 0;
    },

    saveExpense() {
        this.saved = false;

        if (!this.validateForm()) {
            return;
        }

        this.saved = true;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
}));

Alpine.data('financeCollectionPage', () => ({
    activeModal: null,
    saved: false,
    bulkSaved: false,
    errors: {},
    remainingAmount: 0,
    form: {
        business_id: '',
        revenue_id: '',
        invoice_no: '',
        collection_date: '2026-07-07',
        due_date: '2026-07-15',
        total_amount: '',
        collected_amount: '',
        payment_method: '',
        payment_reference: '',
        bank: '',
        description: '',
    },
    bulk: {
        collection_date: '2026-07-07',
        payment_method: 'bank_transfer',
    },

    closeModals() {
        this.activeModal = null;
        this.saved = false;
        this.bulkSaved = false;
        this.errors = {};
        this.resetForm();
    },

    resetForm() {
        this.form = {
            business_id: '',
            revenue_id: '',
            invoice_no: '',
            collection_date: '2026-07-07',
            due_date: '2026-07-15',
            total_amount: '',
            collected_amount: '',
            payment_method: '',
            payment_reference: '',
            bank: '',
            description: '',
        };
        this.remainingAmount = 0;
    },

    calcRemaining() {
        const total = parseFloat(this.form.total_amount) || 0;
        const collected = parseFloat(this.form.collected_amount) || 0;
        this.remainingAmount = Math.max(0, Math.round((total - collected) * 100) / 100);
    },

    formatMoney(amount) {
        return formatMoneyExclVat(amount);
    },

    validateForm() {
        this.errors = {};

        if (!this.form.business_id) {
            this.errors.business_id = 'İşletme seçilmelidir.';
        }

        if (!this.form.total_amount || Number(this.form.total_amount) <= 0) {
            this.errors.total_amount = 'Geçerli bir toplam tutar girin.';
        }

        if (this.form.collected_amount === '' || Number(this.form.collected_amount) < 0) {
            this.errors.collected_amount = 'Geçerli bir tahsil tutarı girin.';
        }

        if (Number(this.form.collected_amount) > Number(this.form.total_amount)) {
            this.errors.collected_amount = 'Tahsil tutarı toplam tutarı aşamaz.';
        }

        return Object.keys(this.errors).length === 0;
    },

    saveCollection() {
        this.saved = false;
        this.calcRemaining();

        if (!this.validateForm()) {
            return;
        }

        this.saved = true;
    },

    saveBulk() {
        this.bulkSaved = false;
        this.bulkSaved = true;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
}));

Alpine.data('financePaymentPage', (recipientsByType = {}) => ({
    activeModal: null,
    saved: false,
    bulkSaved: false,
    errors: {},
    remainingAmount: 0,
    recipientsByType,
    form: {
        recipient_type: '',
        recipient_id: '',
        earning_id: '',
        payment_date: '2026-07-07',
        total_amount: '',
        paid_amount: '',
        payment_method: '',
        bank_account: '',
        payment_reference: '',
        description: '',
    },
    bulk: {
        payment_date: '2026-07-07',
        payment_method: 'bank_transfer',
    },

    get availableRecipients() {
        if (!this.form.recipient_type || !this.recipientsByType[this.form.recipient_type]) {
            return [];
        }

        return this.recipientsByType[this.form.recipient_type];
    },

    closeModals() {
        this.activeModal = null;
        this.saved = false;
        this.bulkSaved = false;
        this.errors = {};
        this.resetForm();
    },

    resetForm() {
        this.form = {
            recipient_type: '',
            recipient_id: '',
            earning_id: '',
            payment_date: '2026-07-07',
            total_amount: '',
            paid_amount: '',
            payment_method: '',
            bank_account: '',
            payment_reference: '',
            description: '',
        };
        this.remainingAmount = 0;
    },

    calcRemaining() {
        const total = parseFloat(this.form.total_amount) || 0;
        const paid = parseFloat(this.form.paid_amount) || 0;
        this.remainingAmount = Math.max(0, Math.round((total - paid) * 100) / 100);
    },

    formatMoney(amount) {
        return formatMoneyExclVat(amount);
    },

    validateForm() {
        this.errors = {};

        if (!this.form.recipient_type) {
            this.errors.recipient_type = 'Alıcı türü seçilmelidir.';
        }

        if (!this.form.recipient_id) {
            this.errors.recipient_id = 'Alıcı seçilmelidir.';
        }

        if (!this.form.payment_date) {
            this.errors.payment_date = 'Ödeme tarihi girilmelidir.';
        }

        if (!this.form.total_amount || Number(this.form.total_amount) <= 0) {
            this.errors.total_amount = 'Geçerli bir toplam tutar girin.';
        }

        if (this.form.paid_amount === '' || Number(this.form.paid_amount) < 0) {
            this.errors.paid_amount = 'Geçerli bir ödenen tutar girin.';
        }

        if (Number(this.form.paid_amount) > Number(this.form.total_amount)) {
            this.errors.paid_amount = 'Ödenen tutar toplam tutarı aşamaz.';
        }

        if (!this.form.payment_method) {
            this.errors.payment_method = 'Ödeme yöntemi seçilmelidir.';
        }

        return Object.keys(this.errors).length === 0;
    },

    savePayment() {
        this.saved = false;
        this.calcRemaining();

        if (!this.validateForm()) {
            return;
        }

        this.saved = true;
    },

    saveBulk() {
        this.bulkSaved = false;
        this.bulkSaved = true;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
}));

Alpine.data('financeInvoicePage', () => ({
    activeModal: null,
    saved: false,
    bulkSaved: false,
    errors: {},
    vatAmount: 0,
    grandTotal: 0,
    form: {
        business_id: '',
        earning_id: '',
        invoice_type: 'e_invoice',
        invoice_date: '2026-07-07',
        due_date: '2026-07-22',
        subtotal: '',
        vat_rate: 20,
        description: '',
    },
    bulk: {
        invoice_type: 'e_invoice',
        invoice_date: '2026-07-07',
        vat_rate: 20,
    },

    closeModals() {
        this.activeModal = null;
        this.saved = false;
        this.bulkSaved = false;
        this.errors = {};
        this.resetForm();
    },

    resetForm() {
        this.form = {
            business_id: '',
            earning_id: '',
            invoice_type: 'e_invoice',
            invoice_date: '2026-07-07',
            due_date: '2026-07-22',
            subtotal: '',
            vat_rate: 20,
            description: '',
        };
        this.vatAmount = 0;
        this.grandTotal = 0;
    },

    calcTotals() {
        const subtotal = parseFloat(this.form.subtotal) || 0;
        const vatRate = parseFloat(this.form.vat_rate) || 0;
        this.vatAmount = Math.round(subtotal * (vatRate / 100) * 100) / 100;
        this.grandTotal = Math.round((subtotal + this.vatAmount) * 100) / 100;
    },

    formatMoney(amount) {
        return formatMoneyExclVat(amount);
    },

    validateForm() {
        this.errors = {};

        if (!this.form.business_id) {
            this.errors.business_id = 'İşletme seçilmelidir.';
        }

        if (!this.form.earning_id) {
            this.errors.earning_id = 'Hakediş seçilmelidir.';
        }

        if (!this.form.subtotal || Number(this.form.subtotal) <= 0) {
            this.errors.subtotal = 'Geçerli bir ara toplam girin.';
        }

        return Object.keys(this.errors).length === 0;
    },

    saveInvoice() {
        this.saved = false;
        this.calcTotals();

        if (!this.validateForm()) {
            return;
        }

        this.saved = true;
    },

    saveBulk() {
        this.bulkSaved = false;
        this.bulkSaved = true;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
}));

Alpine.data('financeProfitabilityPage', () => ({
    chartInstances: [],
    themeObserver: null,

    init() {
        this.$nextTick(() => this.renderCharts());

        this.themeObserver = new MutationObserver(() => this.renderCharts());
        this.themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    },

    destroy() {
        this.themeObserver?.disconnect();
        this.destroyCharts();
    },

    getChartTheme() {
        const isDark = document.documentElement.classList.contains('dark');

        return {
            isDark,
            textColor: isDark ? '#94a3b8' : '#6b7280',
            gridColor: isDark ? '#334155' : '#e5e7eb',
            tooltipTheme: isDark ? 'dark' : 'light',
        };
    },

    destroyCharts() {
        this.chartInstances.forEach((chart) => chart.destroy());
        this.chartInstances = [];
    },

    renderCharts() {
        const raw = this.$el.dataset.charts;
        if (!raw) {
            return;
        }

        const data = JSON.parse(raw);
        const theme = this.getChartTheme();

        this.destroyCharts();

        const baseChartOptions = {
            chart: {
                background: 'transparent',
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            theme: { mode: theme.isDark ? 'dark' : 'light' },
            grid: {
                borderColor: theme.gridColor,
                strokeDashArray: 4,
            },
            tooltip: { theme: theme.tooltipTheme },
        };

        const trendEl = document.querySelector('#profitability-chart-trend');
        if (trendEl) {
            const chart = new ApexCharts(trendEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'line', height: 300 },
                colors: ['#10b981', '#ef4444', '#6366f1'],
                stroke: { curve: 'smooth', width: 2 },
                series: [
                    { name: 'Gelir', data: data.trend.revenue },
                    { name: 'Gider', data: data.trend.expense },
                    { name: 'Kâr', data: data.trend.profit },
                ],
                xaxis: {
                    categories: data.months,
                    labels: { style: { colors: theme.textColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000_000).toFixed(1)}M`,
                    },
                },
                legend: { labels: { colors: theme.textColor }, position: 'top', horizontalAlign: 'right' },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const businessEl = document.querySelector('#profitability-chart-business');
        if (businessEl && data.business_profitability.length > 0) {
            const chart = new ApexCharts(businessEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'bar', height: 300 },
                colors: ['#6366f1'],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
                series: [{ name: 'Net Kâr', data: data.business_profitability.map((i) => i.value) }],
                xaxis: {
                    categories: data.business_profitability.map((i) => i.label),
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000).toFixed(0)}K`,
                    },
                },
                yaxis: { labels: { style: { colors: theme.textColor }, maxWidth: 140 } },
                dataLabels: { enabled: false },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const agencyEl = document.querySelector('#profitability-chart-agency');
        if (agencyEl && data.agency_profitability.length > 0) {
            const chart = new ApexCharts(agencyEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'bar', height: 300 },
                colors: ['#f59e0b'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                series: [{ name: 'Net Kâr', data: data.agency_profitability.map((i) => i.value) }],
                xaxis: {
                    categories: data.agency_profitability.map((i) => i.label),
                    labels: { style: { colors: theme.textColor }, rotate: -25, trim: true, hideOverlappingLabels: true },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000).toFixed(0)}K`,
                    },
                },
                dataLabels: { enabled: false },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const cityEl = document.querySelector('#profitability-chart-city');
        if (cityEl && data.city_profitability.length > 0) {
            const chart = new ApexCharts(cityEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'bar', height: 300 },
                colors: ['#06b6d4'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
                series: [{ name: 'Net Kâr', data: data.city_profitability.map((i) => i.value) }],
                xaxis: {
                    categories: data.city_profitability.map((i) => i.label),
                    labels: { style: { colors: theme.textColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000_000).toFixed(1)}M`,
                    },
                },
                dataLabels: { enabled: false },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const revenueDistEl = document.querySelector('#profitability-chart-revenue-distribution');
        if (revenueDistEl && data.revenue_distribution.length > 0) {
            const chart = new ApexCharts(revenueDistEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'donut', height: 300 },
                colors: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b'],
                labels: data.revenue_distribution.map((i) => i.label),
                series: data.revenue_distribution.map((i) => i.value),
                legend: { position: 'bottom', labels: { colors: theme.textColor } },
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Toplam Gelir',
                                    color: theme.textColor,
                                    formatter: (w) => {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return `${(total / 1_000_000).toFixed(1)}M ₺`;
                                    },
                                },
                            },
                        },
                    },
                },
            });
            chart.render();
            this.chartInstances.push(chart);
        }
    },
}));

Alpine.data('financeCashFlowPage', () => ({
    showCustomRange: false,
    customStart: '',
    customEnd: '',
    chartInstances: [],
    themeObserver: null,

    init() {
        this.showCustomRange = this.$el.dataset.period === 'custom';
        this.customStart = new URLSearchParams(window.location.search).get('start_date') || '';
        this.customEnd = new URLSearchParams(window.location.search).get('end_date') || '';

        this.$nextTick(() => this.renderCharts());

        this.themeObserver = new MutationObserver(() => this.renderCharts());
        this.themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    },

    destroy() {
        this.themeObserver?.disconnect();
        this.destroyCharts();
    },

    selectPeriod(period) {
        const url = new URL(window.location.href);
        url.searchParams.set('period', period);
        url.searchParams.delete('start_date');
        url.searchParams.delete('end_date');
        url.searchParams.delete('page');
        window.location.href = url.toString();
    },

    applyCustomRange() {
        const url = new URL(window.location.href);
        url.searchParams.set('period', 'custom');
        url.searchParams.set('start_date', this.customStart);
        url.searchParams.set('end_date', this.customEnd);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    },

    getChartTheme() {
        const isDark = document.documentElement.classList.contains('dark');

        return {
            isDark,
            textColor: isDark ? '#94a3b8' : '#6b7280',
            gridColor: isDark ? '#334155' : '#e5e7eb',
            tooltipTheme: isDark ? 'dark' : 'light',
        };
    },

    destroyCharts() {
        this.chartInstances.forEach((chart) => chart.destroy());
        this.chartInstances = [];
    },

    renderCharts() {
        const raw = this.$el.dataset.charts;
        if (!raw) {
            return;
        }

        const data = JSON.parse(raw);
        const theme = this.getChartTheme();

        this.destroyCharts();

        const baseChartOptions = {
            chart: {
                background: 'transparent',
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            theme: { mode: theme.isDark ? 'dark' : 'light' },
            grid: {
                borderColor: theme.gridColor,
                strokeDashArray: 4,
            },
            tooltip: { theme: theme.tooltipTheme },
        };

        const balanceEl = document.querySelector('#cashflow-chart-balance');
        if (balanceEl && data.labels.length > 0) {
            const chart = new ApexCharts(balanceEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'line', height: 280 },
                colors: ['#6366f1'],
                stroke: { curve: 'smooth', width: 3 },
                series: [{ name: 'Kasa Bakiyesi', data: data.cash_flow.balance }],
                xaxis: {
                    categories: data.labels,
                    labels: { style: { colors: theme.textColor } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000_000).toFixed(1)}M`,
                    },
                },
                dataLabels: { enabled: false },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const dailyEl = document.querySelector('#cashflow-chart-daily');
        if (dailyEl && data.labels.length > 0) {
            const chart = new ApexCharts(dailyEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'bar', height: 280 },
                colors: ['#10b981', '#ef4444'],
                plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
                series: [
                    { name: 'Giren', data: data.daily_movement.in },
                    { name: 'Çıkan', data: data.daily_movement.out },
                ],
                xaxis: {
                    categories: data.labels,
                    labels: { style: { colors: theme.textColor }, rotate: -45 },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000).toFixed(0)}K`,
                    },
                },
                legend: { labels: { colors: theme.textColor }, position: 'top', horizontalAlign: 'right' },
                dataLabels: { enabled: false },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const distributionEl = document.querySelector('#cashflow-chart-distribution');
        if (distributionEl) {
            const chart = new ApexCharts(distributionEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'donut', height: 280 },
                colors: ['#10b981', '#ef4444'],
                labels: data.distribution.map((i) => i.label),
                series: data.distribution.map((i) => i.value),
                legend: { position: 'bottom', labels: { colors: theme.textColor } },
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Toplam',
                                    color: theme.textColor,
                                    formatter: (w) => {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return `${(total / 1_000_000).toFixed(1)}M ₺`;
                                    },
                                },
                            },
                        },
                    },
                },
            });
            chart.render();
            this.chartInstances.push(chart);
        }

        const pendingEl = document.querySelector('#cashflow-chart-pending');
        if (pendingEl) {
            const chart = new ApexCharts(pendingEl, {
                ...baseChartOptions,
                chart: { ...baseChartOptions.chart, type: 'bar', height: 280, stacked: true },
                colors: ['#10b981', '#ef4444'],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '45%' } },
                series: [
                    { name: 'Bekleyen Tahsilatlar', data: [data.pending_comparison.collections] },
                    { name: 'Bekleyen Ödemeler', data: [data.pending_comparison.payments] },
                ],
                xaxis: {
                    categories: ['Bekleyen Nakit'],
                    labels: {
                        style: { colors: theme.textColor },
                        formatter: (value) => `${(value / 1_000_000).toFixed(1)}M`,
                    },
                },
                yaxis: { labels: { style: { colors: theme.textColor } } },
                legend: { labels: { colors: theme.textColor }, position: 'top', horizontalAlign: 'right' },
                dataLabels: { enabled: false },
            });
            chart.render();
            this.chartInstances.push(chart);
        }
    },
}));

Alpine.data('userManagementPage', () => ({
    activeModal: null,
    saved: false,
    errors: {},
    form: {
        first_name: '',
        last_name: '',
        phone: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [],
        linked_business_id: '',
        linked_courier_id: '',
        linked_agency_id: '',
        status: 'active',
    },

    closeModals() {
        this.activeModal = null;
        this.saved = false;
        this.errors = {};
        this.resetForm();
    },

    resetForm() {
        this.form = {
            first_name: '',
            last_name: '',
            phone: '',
            email: '',
            password: '',
            password_confirmation: '',
            roles: [],
            linked_business_id: '',
            linked_courier_id: '',
            linked_agency_id: '',
            status: 'active',
        };
    },

    validateForm() {
        this.errors = {};

        if (!this.form.first_name?.trim()) {
            this.errors.first_name = 'Ad zorunludur.';
        }

        if (!this.form.last_name?.trim()) {
            this.errors.last_name = 'Soyad zorunludur.';
        }

        if (!this.form.phone?.trim()) {
            this.errors.phone = 'Telefon zorunludur.';
        }

        if (!this.form.email?.trim()) {
            this.errors.email = 'E-posta zorunludur.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
            this.errors.email = 'Geçerli bir e-posta adresi girin.';
        }

        if (!this.form.password) {
            this.errors.password = 'Şifre zorunludur.';
        } else if (this.form.password.length < 8) {
            this.errors.password = 'Şifre en az 8 karakter olmalıdır.';
        }

        if (this.form.password !== this.form.password_confirmation) {
            this.errors.password_confirmation = 'Şifreler eşleşmiyor.';
        }

        if (!this.form.roles?.length) {
            this.errors.roles = 'En az bir rol seçilmelidir.';
        }

        return Object.keys(this.errors).length === 0;
    },

    saveUser() {
        this.saved = false;

        if (!this.validateForm()) {
            return;
        }

        this.saved = true;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
}));

Alpine.data('roleManagementPage', () => ({
    activeModal: null,
    saved: false,
    errors: {},
    form: {
        display_name: '',
        description: '',
        status: 'active',
    },

    closeModals() {
        this.activeModal = null;
        this.saved = false;
        this.errors = {};
        this.resetForm();
    },

    resetForm() {
        this.form = {
            display_name: '',
            description: '',
            status: 'active',
        };
    },

    validateForm() {
        this.errors = {};

        if (!this.form.display_name?.trim()) {
            this.errors.display_name = 'Rol adı zorunludur.';
        }

        return Object.keys(this.errors).length === 0;
    },

    saveRole() {
        this.saved = false;

        if (!this.validateForm()) {
            return;
        }

        this.saved = true;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
            this.saved = false;
        }
    },
}));

Alpine.data('permissionManagementPage', (rolesPayload = {}, initialRole = 'general_manager', summary = {}) => ({
    rolesPayload,
    selectedRole: initialRole,
    previousRole: initialRole,
    matrix: [],
    defaults: [],
    isLocked: false,
    dirty: false,
    saved: false,
    moduleSearch: '',
    permissionSearch: '',
    activeCount: summary.active_permissions ?? 0,
    inactiveCount: summary.inactive_permissions ?? 0,

    init() {
        this.loadRole(this.selectedRole, false);

        window.addEventListener('beforeunload', (event) => {
            if (this.dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    },

    get filteredMatrix() {
        const moduleNeedle = this.moduleSearch.trim().toLowerCase();
        const permissionNeedle = this.permissionSearch.trim().toLowerCase();

        return this.matrix.filter((row) => {
            if (moduleNeedle) {
                const moduleHaystack = [row.label, ...(row.search_terms ?? [])].join(' ').toLowerCase();

                if (! moduleHaystack.includes(moduleNeedle)) {
                    return false;
                }
            }

            if (permissionNeedle) {
                const slugs = Object.values(row.actions)
                    .filter((action) => action?.applicable)
                    .flatMap((action) => action.slugs ?? [])
                    .join(' ')
                    .toLowerCase();

                const permissionHaystack = `${row.label} ${slugs}`.toLowerCase();

                if (! permissionHaystack.includes(permissionNeedle)) {
                    return false;
                }
            }

            return true;
        });
    },

    get activeCountFormatted() {
        return new Intl.NumberFormat('tr-TR').format(this.activeCount);
    },

    get inactiveCountFormatted() {
        return new Intl.NumberFormat('tr-TR').format(this.inactiveCount);
    },

    loadRole(roleSlug, resetPrevious = true) {
        const role = this.rolesPayload[roleSlug];

        if (! role) {
            return;
        }

        this.matrix = JSON.parse(JSON.stringify(role.matrix));
        this.defaults = [...role.defaults];
        this.isLocked = role.is_locked;
        this.dirty = false;
        this.saved = false;
        this.updateCounts();

        if (resetPrevious) {
            this.previousRole = roleSlug;
        }
    },

    changeRole() {
        if (this.dirty) {
            const confirmed = window.confirm('Kaydedilmemiş değişiklikler var. Rol değiştirmek istiyor musunuz?');

            if (! confirmed) {
                this.selectedRole = this.previousRole;

                return;
            }
        }

        this.loadRole(this.selectedRole);

        const url = new URL(window.location.href);
        url.searchParams.set('role', this.selectedRole);
        window.history.replaceState({}, '', url);
    },

    findRow(rowKey) {
        return this.matrix.find((row) => row.key === rowKey);
    },

    toggleCell(rowKey, actionKey, checked) {
        if (this.isLocked) {
            return;
        }

        const row = this.findRow(rowKey);

        if (! row?.actions?.[actionKey]?.applicable) {
            return;
        }

        row.actions[actionKey].granted = checked;
        this.markDirty();
    },

    toggleRow(rowKey, checked) {
        if (this.isLocked) {
            return;
        }

        const row = this.findRow(rowKey);

        if (! row) {
            return;
        }

        Object.keys(row.actions).forEach((actionKey) => {
            if (row.actions[actionKey]?.applicable) {
                row.actions[actionKey].granted = checked;
            }
        });

        this.markDirty();
    },

    rowHasApplicable(row) {
        return Object.values(row.actions).some((action) => action?.applicable);
    },

    isRowFullyGranted(row) {
        const applicable = Object.values(row.actions).filter((action) => action?.applicable);

        return applicable.length > 0 && applicable.every((action) => action.granted);
    },

    selectAll() {
        if (this.isLocked) {
            return;
        }

        this.matrix.forEach((row) => {
            Object.keys(row.actions).forEach((actionKey) => {
                if (row.actions[actionKey]?.applicable) {
                    row.actions[actionKey].granted = true;
                }
            });
        });

        this.markDirty();
    },

    deselectAll() {
        if (this.isLocked) {
            return;
        }

        this.matrix.forEach((row) => {
            Object.keys(row.actions).forEach((actionKey) => {
                if (row.actions[actionKey]?.applicable) {
                    row.actions[actionKey].granted = false;
                }
            });
        });

        this.markDirty();
    },

    resetToDefault() {
        if (this.isLocked) {
            return;
        }

        const granted = new Set(this.rolesPayload[this.selectedRole]?.defaults ?? this.defaults);
        const baseMatrix = this.rolesPayload[this.selectedRole]?.matrix ?? [];

        this.matrix = JSON.parse(JSON.stringify(baseMatrix));

        this.matrix.forEach((row) => {
            Object.keys(row.actions).forEach((actionKey) => {
                const action = row.actions[actionKey];

                if (! action?.applicable) {
                    return;
                }

                action.granted = action.slugs.some((slug) => granted.has(slug));
            });
        });

        this.markDirty();
    },

    collectGrantedSlugs() {
        const slugs = [];

        this.matrix.forEach((row) => {
            Object.values(row.actions).forEach((action) => {
                if (action?.applicable && action.granted && action.primary_slug) {
                    slugs.push(action.primary_slug);
                }
            });
        });

        return [...new Set(slugs)];
    },

    save() {
        if (this.isLocked) {
            return;
        }

        this.saved = true;
        this.dirty = false;

        // Gerçek entegrasyonda: PermissionManagementDummyData::auditLogPayload(...)
        console.info('[CRMLog] permission_matrix_saved', {
            role: this.selectedRole,
            permissions: this.collectGrantedSlugs(),
        });
    },

    markDirty() {
        this.dirty = true;
        this.saved = false;
        this.updateCounts();
    },

    updateCounts() {
        let active = 0;
        let inactive = 0;

        this.matrix.forEach((row) => {
            Object.values(row.actions).forEach((action) => {
                if (! action?.applicable) {
                    return;
                }

                if (action.granted) {
                    active += 1;
                } else {
                    inactive += 1;
                }
            });
        });

        this.activeCount = active;
        this.inactiveCount = inactive;
    },
}));

Alpine.data('fileUpload', (config = {}) => ({
    accept: config.accept || 'image/png,image/jpeg,image/jpg,image/webp',
    maxSizeMb: config.maxSizeMb ?? 2,
    currentUrl: config.currentUrl || null,
    preview: config.currentUrl || null,
    fileName: config.currentUrl ? 'Mevcut dosya' : '',
    localError: '',
    dragging: false,

    handleSelect(event) {
        const file = event.target.files?.[0];
        this.processFile(file, event.target);
    },

    processFile(file, input = null) {
        this.localError = '';

        if (!file) {
            this.fileName = this.currentUrl ? 'Mevcut dosya' : '';
            this.preview = this.currentUrl || null;

            return;
        }

        if (this.maxSizeMb && file.size > this.maxSizeMb * 1024 * 1024) {
            this.localError = `Dosya boyutu en fazla ${this.maxSizeMb} MB olabilir.`;

            if (input) {
                input.value = '';
            }

            return;
        }

        if (!this.matchesAccept(file)) {
            this.localError = 'Seçilen dosya türü desteklenmiyor.';

            if (input) {
                input.value = '';
            }

            return;
        }

        this.fileName = file.name;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (event) => {
                this.preview = event.target?.result ?? null;
            };
            reader.readAsDataURL(file);

            return;
        }

        this.preview = null;
    },

    matchesAccept(file) {
        if (!this.accept) {
            return true;
        }

        const tokens = this.accept.split(',').map((token) => token.trim().toLowerCase());
        const fileName = file.name.toLowerCase();
        const mime = (file.type || '').toLowerCase();

        return tokens.some((token) => {
            if (token.startsWith('.')) {
                return fileName.endsWith(token);
            }

            if (token.endsWith('/*')) {
                return mime.startsWith(token.slice(0, -1));
            }

            return mime === token;
        });
    },

    handleDrop(event) {
        this.dragging = false;
        const file = event.dataTransfer?.files?.[0];

        if (!file) {
            return;
        }

        const input = this.$refs.fileInput;

        if (input && event.dataTransfer?.files) {
            input.files = event.dataTransfer.files;
        }

        this.processFile(file, input);
    },

    clear() {
        this.fileName = '';
        this.localError = '';
        this.preview = this.currentUrl || null;

        if (this.$refs.fileInput) {
            this.$refs.fileInput.value = '';
        }
    },
}));

Alpine.data('settingsImageUpload', (initialUrl = null) => ({
    preview: initialUrl,
    dragging: false,

    handleFile(file) {
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            this.preview = event.target?.result;
        };
        reader.readAsDataURL(file);
    },

    handleDrop(event) {
        this.dragging = false;
        const file = event.dataTransfer?.files?.[0];
        if (file) {
            this.$refs.fileInput.files = event.dataTransfer.files;
            this.handleFile(file);
        }
    },
}));

Alpine.data('systemSettingsPage', () => ({
    dirty: false,
    saving: false,
    testMessage: '',

    markSaving() {
        this.saving = true;
        this.dirty = false;
    },

    init() {
        window.addEventListener('beforeunload', (event) => {
            if (this.dirty && !this.saving) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        window.addEventListener('settings-test-mail', () => this.showTest('Test maili gönderim kuyruğuna alındı.'));
        window.addEventListener('settings-test-sms', () => this.showTest('Test SMS gönderim kuyruğuna alındı.'));
        window.addEventListener('settings-backup-manual', () => this.showTest('Manuel yedekleme başlatıldı.'));
    },

    showTest(message) {
        this.testMessage = message;
        setTimeout(() => {
            this.testMessage = '';
        }, 3000);
    },
}));

Alpine.data('userActivityLogPage', (logsMap = {}) => ({
    activeModal: false,
    selected: null,
    logsMap,

    openDetail(event) {
        const id = event.detail?.id;
        this.selected = this.logsMap[id] ?? null;
        this.activeModal = this.selected !== null;
    },

    closeModal() {
        this.activeModal = false;
        this.selected = null;
    },
}));

Alpine.data('financeActivityLogPage', (logsMap = {}) => ({
    activeModal: false,
    selected: null,
    logsMap,

    openDetail(event) {
        const id = event.detail?.id;
        this.selected = this.logsMap[id] ?? null;
        this.activeModal = this.selected !== null;
    },

    closeModal() {
        this.activeModal = false;
        this.selected = null;
    },
}));

Alpine.data('formBuilderListPage', () => ({
    handleDelete(detail) {
        if (detail?.action !== 'delete' || !detail?.id) {
            return;
        }

        if (detail?.confirm && !window.confirm(detail.confirm)) {
            return;
        }

        document.getElementById(`delete-form-${detail.id}`)?.submit();
    },
}));

Alpine.data('formBuilderPage', (form = {}, fieldTypes = {}) => ({
    fields: Array.isArray(form.fields) ? JSON.parse(JSON.stringify(form.fields)) : [],
    fieldTypes,
    selectedId: null,
    previewOpen: false,
    meta: {
        name: form.name ?? '',
        description: form.description ?? '',
        status: form.status ?? 'draft',
    },

    get selectedField() {
        return this.fields.find((field) => field.id === this.selectedId) ?? null;
    },

    typeLabel(type) {
        return this.fieldTypes[type]?.label ?? type;
    },

    selectField(id) {
        this.selectedId = id;
    },

    addField(type) {
        const index = this.fields.filter((field) => field.type === type).length + 1;
        const field = this.createField(type, index);
        this.fields.push(field);
        this.selectedId = field.id;
    },

    createField(type, index) {
        const palette = this.fieldTypes[type] ?? { label: 'Alan' };
        const id = `field_${Math.random().toString(16).slice(2, 10)}`;
        const baseName = `${type.replace('-', '_')}_${index}`;

        const field = {
            id,
            type,
            label: palette.label,
            name: baseName,
            placeholder: '',
            help_text: '',
            required: !['heading', 'checkbox'].includes(type),
            width: 'full',
            options: [],
        };

        if (type === 'heading') {
            field.required = false;
            field.name = `heading_${index}`;
            field.placeholder = 'Bölüm açıklaması (isteğe bağlı)';
        }

        if (type === 'checkbox') {
            field.label = 'Onaylıyorum';
            field.required = false;
        }

        if (['select', 'radio'].includes(type)) {
            field.options = ['Seçenek 1', 'Seçenek 2', 'Seçenek 3'];
        }

        return field;
    },

    removeField(index) {
        const removed = this.fields[index];
        this.fields.splice(index, 1);

        if (this.selectedId === removed?.id) {
            this.selectedId = this.fields[index]?.id ?? this.fields[index - 1]?.id ?? null;
        }
    },

    duplicateField(index) {
        const copy = JSON.parse(JSON.stringify(this.fields[index]));
        copy.id = `field_${Math.random().toString(16).slice(2, 10)}`;
        copy.name = `${copy.name}_kopya`;
        this.fields.splice(index + 1, 0, copy);
        this.selectedId = copy.id;
    },

    moveField(index, direction) {
        const target = index + direction;

        if (target < 0 || target >= this.fields.length) {
            return;
        }

        const [item] = this.fields.splice(index, 1);
        this.fields.splice(target, 0, item);
    },

    syncFields(event) {
        this.$refs.fieldsJson.value = JSON.stringify(this.fields);
    },
}));

function normalizeLandingPageContent(html) {
    if (!html) {
        return '';
    }

    const trimmed = html.trim();

    if (trimmed === '' || trimmed === '<p><br></p>') {
        return '';
    }

    const container = document.createElement('div');
    container.innerHTML = trimmed;

    const isEmptyParagraph = (element) => {
        if (element.tagName !== 'P') {
            return false;
        }

        return element.innerHTML === '<br>' || element.textContent.trim() === '';
    };

    while (container.firstElementChild && isEmptyParagraph(container.firstElementChild)) {
        container.firstElementChild.remove();
    }

    while (container.lastElementChild && isEmptyParagraph(container.lastElementChild)) {
        container.lastElementChild.remove();
    }

    let previousWasEmpty = false;

    Array.from(container.querySelectorAll('p')).forEach((paragraph) => {
        const empty = isEmptyParagraph(paragraph);

        if (empty && previousWasEmpty) {
            paragraph.remove();
        }

        previousWasEmpty = empty;
    });

    return container.innerHTML.trim();
}

Alpine.data('landingPageBuilderListPage', () => ({
    handleDelete(detail) {
        if (detail?.action !== 'delete' || !detail?.id) {
            return;
        }

        if (detail?.confirm && !window.confirm(detail.confirm)) {
            return;
        }

        document.getElementById(`delete-form-${detail.id}`)?.submit();
    },
}));

Alpine.data('landingPageBuilderPage', (page = {}, forms = []) => ({
    forms,
    heroPreview: page.hero_image_url ?? null,
    quill: null,
    meta: {
        name: page.name ?? '',
        slug: page.slug ?? '',
        status: page.status ?? 'draft',
        title: page.title ?? '',
        content: normalizeLandingPageContent(page.content ?? ''),
        form_id: page.form_id ? String(page.form_id) : '',
        meta_title: page.meta_title ?? '',
        meta_description: page.meta_description ?? '',
    },

    get selectedFormFields() {
        const formId = Number(this.meta.form_id);

        if (!formId) {
            return [];
        }

        return this.forms.find((form) => form.id === formId)?.fields ?? [];
    },

    get contentPreview() {
        const html = normalizeLandingPageContent(this.meta.content ?? '');

        if (!html) {
            return '<p class="text-sm text-gray-500">Metin alanı burada görünecek.</p>';
        }

        return html;
    },

    init() {
        this.$nextTick(() => this.initEditor());
    },

    initEditor() {
        if (!this.$refs.contentEditor || this.quill) {
            return;
        }

        this.quill = new Quill(this.$refs.contentEditor, {
            theme: 'snow',
            placeholder: 'Sayfa açıklama metni...',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'blockquote'],
                    ['clean'],
                ],
                clipboard: {
                    matchVisual: false,
                },
            },
        });

        this.quill.root.classList.add('landing-page-content');

        if (this.meta.content) {
            this.quill.root.innerHTML = this.meta.content;
        }

        this.quill.on('text-change', () => {
            this.meta.content = this.quill.root.innerHTML;
        });
    },

    syncContent() {
        if (this.quill) {
            this.meta.content = normalizeLandingPageContent(this.quill.root.innerHTML);
            this.quill.root.innerHTML = this.meta.content || '';
        }

        if (this.$refs.contentInput) {
            this.$refs.contentInput.value = this.meta.content;
        }
    },

    onHeroChange(event) {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        this.heroPreview = URL.createObjectURL(file);
    },
}));

Alpine.data('policySettingsPage', (policies = {}) => ({
    policies: Object.fromEntries(
        Object.entries(policies).map(([key, policy]) => [
            key,
            {
                title: policy.title ?? '',
                content: normalizeLandingPageContent(policy.content ?? ''),
                meta_title: policy.meta_title ?? '',
                meta_description: policy.meta_description ?? '',
            },
        ]),
    ),
    quills: {},

    init() {
        this.$nextTick(() => {
            Object.keys(this.policies).forEach((key) => this.initEditor(key));
        });
    },

    initEditor(key) {
        const ref = this.$refs[`editor_${key}`];

        if (!ref || this.quills[key]) {
            return;
        }

        const quill = new Quill(ref, {
            theme: 'snow',
            placeholder: 'Politika metni...',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'blockquote'],
                    ['clean'],
                ],
                clipboard: {
                    matchVisual: false,
                },
            },
        });

        quill.root.classList.add('landing-page-content');

        if (this.policies[key].content) {
            quill.root.innerHTML = this.policies[key].content;
        }

        quill.on('text-change', () => {
            this.policies[key].content = quill.root.innerHTML;
        });

        this.quills[key] = quill;
    },

    syncAll() {
        Object.keys(this.quills).forEach((key) => {
            this.policies[key].content = normalizeLandingPageContent(this.quills[key].root.innerHTML);
            this.quills[key].root.innerHTML = this.policies[key].content || '';

            const input = this.$refs[`input_${key}`];

            if (input) {
                input.value = this.policies[key].content;
            }
        });
    },
}));

Alpine.start();
