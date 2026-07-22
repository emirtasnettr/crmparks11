import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import ApexCharts from 'apexcharts';
import Quill from 'quill';
import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'quill/dist/quill.snow.css';
import 'leaflet/dist/leaflet.css';

Alpine.plugin(collapse);

window.Alpine = Alpine;
window.L = L;

// Vite paketlemesinde Leaflet varsayılan ikon yollarını düzelt.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const lockedPresetId = (preset, key) => (preset?.[key] ? String(preset[key]) : '');

const todayDateInput = () => {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

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

    return `${formatted} ₺`;
};

const formatMoneyExclVat = (amount, decimals = 2) => window.formatMoneyExcludingVat(amount, decimals);

Alpine.data('topNav', () => ({
    mobileOpen: false,
    openDropdown: null,
    sidebarCollapsed: (() => {
        try {
            return localStorage.getItem('crmlog.sidebarCollapsed') === '1';
        } catch (e) {
            return false;
        }
    })(),
    toast: null,
    _sidebarAnimTimer: null,

    setSidebarCollapsed(collapsed) {
        if (this.sidebarCollapsed === collapsed) {
            return;
        }

        const sidebar = document.querySelector('.app-sidebar');
        if (sidebar) {
            sidebar.classList.add('app-sidebar--animating');
        }

        this.sidebarCollapsed = collapsed;

        try {
            localStorage.setItem('crmlog.sidebarCollapsed', collapsed ? '1' : '0');
            document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        } catch (e) {
            // Ignore storage failures (private mode, etc.).
        }

        if (this._sidebarAnimTimer) {
            window.clearTimeout(this._sidebarAnimTimer);
        }

        this._sidebarAnimTimer = window.setTimeout(() => {
            if (sidebar) {
                sidebar.classList.remove('app-sidebar--animating');
            }
            this._sidebarAnimTimer = null;
        }, 220);
    },

    toggleSidebar() {
        this.setSidebarCollapsed(! this.sidebarCollapsed);
    },

    toggleDropdown(key) {
        this.openDropdown = this.openDropdown === key ? null : key;
    },

    closeDropdown(key) {
        if (this.openDropdown === key) {
            this.openDropdown = null;
        }
    },

    handleCrmlogAction(detail) {
        if (detail?.confirm && ! window.confirm(detail.confirm)) {
            return;
        }

        if (detail?.url) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = detail.url;
            form.style.display = 'none';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
            form.appendChild(csrf);

            const method = String(detail.method || 'POST').toUpperCase();
            if (method !== 'POST') {
                const spoof = document.createElement('input');
                spoof.type = 'hidden';
                spoof.name = '_method';
                spoof.value = method;
                form.appendChild(spoof);
            }

            document.body.appendChild(form);
            form.submit();
            return;
        }

        this.toast = detail?.message || `${detail?.label ?? 'İşlem'} tamamlandı.`;

        window.setTimeout(() => {
            this.toast = null;
        }, 3500);
    },
}));

Alpine.data('courierShiftLocation', () => ({
    loading: false,
    error: '',
    latitude: '',
    longitude: '',
    accuracy: '',

    async submit(form) {
        this.error = '';
        this.loading = true;

        if (! navigator.geolocation) {
            this.loading = false;
            this.error = 'Bu cihazda konum alınamıyor.';
            return;
        }

        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0,
                });
            });

            this.latitude = String(position.coords.latitude);
            this.longitude = String(position.coords.longitude);
            this.accuracy = position.coords.accuracy != null
                ? String(Math.round(position.coords.accuracy))
                : '';

            this.$nextTick(() => form.submit());
        } catch (err) {
            this.loading = false;
            if (err?.code === 1) {
                this.error = 'Konum izni reddedildi. İşlem için konum izni verin.';
            } else if (err?.code === 3) {
                this.error = 'Konum alınamadı (zaman aşımı). Açık alanda tekrar deneyin.';
            } else {
                this.error = 'Konum alınamadı. Tekrar deneyin.';
            }
        }
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

Alpine.data('businessForm', (districtsByCity = {}, initial = {}, isEdit = false, earningsEnabled = true, geocodeUrl = '', neighborhoodsUrl = '') => ({
    districtsByCity,
    districts: [],
    neighborhoods: [],
    loadingNeighborhoods: false,
    errors: {},
    submitted: false,
    submitting: false,
    validated: false,
    isEdit,
    earningsEnabled,
    geocodeUrl,
    neighborhoodsUrl,
    geocoding: false,
    geocodeMessage: '',
    geocodeLabel: '',
    pinManuallyAdjusted: false,
    geocodeTimer: null,
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
        neighborhood: '',
        address: '',
        latitude: '',
        longitude: '',
        earning_period: '',
        first_invoice_date: '',
        planned_courier_count: '',
        status: 'active',
        contract_end_date: '',
        estimated_opening_date: '',
        start_date: '',
        notes: '',
        ...initial,
    },
    map: null,
    mapMarker: null,

    init() {
        if (this.form.latitude === null || this.form.latitude === undefined) {
            this.form.latitude = '';
        }
        if (this.form.longitude === null || this.form.longitude === undefined) {
            this.form.longitude = '';
        }
        if (this.form.neighborhood === null || this.form.neighborhood === undefined) {
            this.form.neighborhood = '';
        }

        // Düzenlemede mevcut pin varsa otomatik geocode üzerine yazmasın.
        if (String(this.form.latitude).trim() !== '' && String(this.form.longitude).trim() !== '') {
            this.pinManuallyAdjusted = true;
        }

        if (!this.form.first_invoice_date) {
            const next = new Date();
            next.setDate(1);
            next.setMonth(next.getMonth() + 1);
            const year = next.getFullYear();
            const month = String(next.getMonth() + 1).padStart(2, '0');
            this.form.first_invoice_date = `${year}-${month}-01`;
        }

        if (this.form.city) {
            this.districts = this.districtsByCity[this.form.city] || [];
        }

        if (this.form.city && this.form.district) {
            this.loadNeighborhoods({ preserveSelection: true });
        }

        const params = new URLSearchParams(window.location.search);
        const presetStatus = params.get('status');
        if (presetStatus && ['active', 'inactive', 'pending', 'contract_stage', 'opening_stage'].includes(presetStatus)) {
            this.form.status = presetStatus;
        }

        this.$nextTick(() => this.initLocationMap());

        this.$watch('form.neighborhood', () => this.scheduleAddressGeocode());
        this.$watch('form.address', () => this.scheduleAddressGeocode());
    },

    scheduleAddressGeocode() {
        if (! this.geocodeUrl || this.pinManuallyAdjusted) {
            return;
        }

        clearTimeout(this.geocodeTimer);
        this.geocodeTimer = setTimeout(() => this.geocodeAddress(false), 900);
    },

    async loadNeighborhoods({ preserveSelection = false } = {}) {
        const city = String(this.form.city || '').trim();
        const district = String(this.form.district || '').trim();
        const selected = preserveSelection ? String(this.form.neighborhood || '').trim() : '';

        if (! preserveSelection) {
            this.form.neighborhood = '';
        }
        this.neighborhoods = [];

        if (! this.neighborhoodsUrl || ! city || ! district) {
            return;
        }

        this.loadingNeighborhoods = true;

        try {
            const params = new URLSearchParams({ city, district });
            const response = await fetch(`${this.neighborhoodsUrl}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));
            this.neighborhoods = Array.isArray(payload.neighborhoods) ? payload.neighborhoods : [];

            if (preserveSelection && selected && this.neighborhoods.includes(selected)) {
                this.form.neighborhood = selected;
            } else if (! preserveSelection) {
                this.form.neighborhood = '';
            }
        } catch (error) {
            this.neighborhoods = [];
        } finally {
            this.loadingNeighborhoods = false;
        }
    },

    async geocodeAddress(force = false) {
        if (force && typeof force === 'object') {
            force = Boolean(force.force);
        }
        force = Boolean(force);

        if (! this.geocodeUrl) {
            this.geocodeMessage = 'Harita arama adresi tanımlı değil. Sayfayı yenileyip tekrar deneyin.';
            return;
        }

        if (! force && this.pinManuallyAdjusted) {
            return;
        }

        const city = String(this.form.city || '').trim();
        const district = String(this.form.district || '').trim();
        const neighborhood = String(this.form.neighborhood || '').trim();
        const address = String(this.form.address || '').trim();

        if (! city) {
            this.geocodeMessage = 'Önce il seçin.';
            return;
        }

        if (! district && ! neighborhood && ! address) {
            this.geocodeMessage = 'İlçe / mahalle seçin veya açık adres girin.';
            return;
        }

        if (force) {
            this.pinManuallyAdjusted = false;
        }

        this.geocoding = true;
        this.geocodeMessage = 'Adres aranıyor...';
        this.geocodeLabel = '';

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const response = await fetch(this.geocodeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ city, district, neighborhood, address }),
            });

            const payload = await response.json().catch(() => ({}));

            if (! response.ok) {
                const detail = payload.message
                    || (response.status === 419
                        ? 'Oturum süresi doldu. Sayfayı yenileyip tekrar deneyin.'
                        : response.status === 403
                            ? 'Bu işlem için yetkiniz yok.'
                            : `Adres haritada bulunamadı (${response.status}).`);
                this.geocodeMessage = detail;
                return;
            }

            const lat = Number(payload.latitude);
            const lng = Number(payload.longitude);
            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                this.geocodeMessage = 'Adres haritada bulunamadı.';
                return;
            }

            this.setMapPoint(lat, lng, { fromGeocode: true });
            this.geocodeMessage = 'Adres haritada işaretlendi. İsterseniz pini sürükleyerek düzeltebilirsiniz.';
            this.geocodeLabel = payload.label ? String(payload.label) : '';
        } catch (error) {
            this.geocodeMessage = 'Adres aranamadı. Bağlantınızı kontrol edip tekrar deneyin.';
        } finally {
            this.geocoding = false;
        }
    },

    setMapPoint(nextLat, nextLng, { fromGeocode = false, fromUser = false } = {}) {
        this.form.latitude = String(Number(nextLat).toFixed(7));
        this.form.longitude = String(Number(nextLng).toFixed(7));

        if (fromUser) {
            this.pinManuallyAdjusted = true;
        }

        const leaflet = window.L;
        if (! leaflet || ! this.map) {
            return;
        }

        if (this.mapMarker) {
            this.mapMarker.setLatLng([nextLat, nextLng]);
        } else {
            this.mapMarker = leaflet.marker([nextLat, nextLng], { draggable: true }).addTo(this.map);
            this.mapMarker.on('dragend', () => {
                const pos = this.mapMarker.getLatLng();
                this.setMapPoint(pos.lat, pos.lng, { fromUser: true });
            });
        }

        if (fromGeocode) {
            this.map.setView([nextLat, nextLng], 16);
        }
    },

    initLocationMap(attempt = 0) {
        const el = this.$refs.businessMap || document.getElementById('business-location-map');
        if (! el) {
            if (attempt < 40) {
                setTimeout(() => this.initLocationMap(attempt + 1), 50);
            }
            return;
        }

        if (this.map || el.dataset.mapReady === '1') {
            return;
        }

        const leaflet = window.L;
        if (! leaflet) {
            if (attempt < 40) {
                setTimeout(() => this.initLocationMap(attempt + 1), 50);
            }
            return;
        }

        const latValue = this.form.latitude;
        const lngValue = this.form.longitude;
        const hasPoint = latValue !== null && latValue !== undefined && String(latValue).trim() !== ''
            && lngValue !== null && lngValue !== undefined && String(lngValue).trim() !== ''
            && ! Number.isNaN(Number(latValue))
            && ! Number.isNaN(Number(lngValue));
        const lat = hasPoint ? Number(latValue) : 41.0082;
        const lng = hasPoint ? Number(lngValue) : 28.9784;

        this.map = leaflet.map(el, { scrollWheelZoom: true }).setView([lat, lng], hasPoint ? 16 : 11);
        leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(this.map);

        if (hasPoint) {
            this.setMapPoint(lat, lng);
        }

        this.map.on('click', (event) => {
            this.setMapPoint(event.latlng.lat, event.latlng.lng, { fromUser: true });
        });

        el.dataset.mapReady = '1';
        setTimeout(() => this.map?.invalidateSize(), 100);
        setTimeout(() => this.map?.invalidateSize(), 400);

        if (! hasPoint) {
            this.scheduleAddressGeocode();
        }
    },

    onCityChange() {
        this.form.district = '';
        this.form.neighborhood = '';
        this.neighborhoods = [];
        this.districts = this.districtsByCity[this.form.city] || [];
        this.pinManuallyAdjusted = false;
        this.scheduleAddressGeocode();
    },

    onDistrictChange() {
        this.form.neighborhood = '';
        this.pinManuallyAdjusted = false;
        this.loadNeighborhoods();
        this.scheduleAddressGeocode();
    },

    validate() {
        this.errors = {};

        if (!this.form.company_name.trim()) {
            this.errors.company_name = 'Firma ünvanı zorunludur.';
        }

        if (!this.form.brand_name.trim()) {
            this.errors.brand_name = 'Marka adı zorunludur.';
        }

        if (!this.form.phone.trim()) {
            this.errors.phone = 'Telefon numarası zorunludur.';
        }

        if (this.earningsEnabled && !this.form.earning_period) {
            this.errors.earning_period = 'Fatura periyodu seçilmelidir.';
        }

        if (this.earningsEnabled && this.form.earning_period && !this.form.first_invoice_date) {
            this.errors.first_invoice_date = 'İlk fatura tarihi zorunludur.';
        }

        const plannedCount = Number(this.form.planned_courier_count);
        if (!this.form.planned_courier_count || Number.isNaN(plannedCount) || plannedCount < 1) {
            this.errors.planned_courier_count = 'Planlanan kurye sayısı zorunludur (en az 1).';
        }

        if (this.form.status === 'inactive' && !this.form.contract_end_date) {
            this.errors.contract_end_date = 'Pasif durum için sözleşme bitiş tarihi zorunludur.';
        }

        if ((this.form.status === 'pending' || this.form.status === 'contract_stage') && !this.form.estimated_opening_date) {
            this.errors.estimated_opening_date = 'Tahmini açılış tarihi zorunludur.';
        }

        if (this.form.status === 'opening_stage' && !this.form.start_date) {
            this.errors.start_date = 'Açılış aşaması için başlangıç tarihi zorunludur.';
        }

        if (!this.form.latitude || !this.form.longitude) {
            this.errors.latitude = 'İşletme konumu haritada işaretlenmelidir.';
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
        tax_number: '',
        city: '',
        district: '',
        status: 'active',
        notes: '',
        ...initial,
    },

    init() {
        if (this.form.city) {
            this.districts = this.districtsByCity[this.form.city] || [];
        }
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

        if (!this.form.brand_name.trim()) {
            this.errors.brand_name = 'Marka adı zorunludur.';
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

Alpine.data('commercialContractPage', (preset = {}) => {
    const contractsById = preset.contractsById ?? {};
    const routes = preset.routes ?? {};
    const today = preset.today ?? new Date().toISOString().slice(0, 10);

    const blankForm = () => ({
        start_date: today,
        end_date: '',
        work_type: 'hourly',
        business_amount: '',
        courier_amount: '',
        guaranteed_hourly_package_fee: '',
        guaranteed_package_count: '',
        payment_period: 'monthly',
        notes: '',
    });

    return {
        contractsById,
        routes,
        today,
        openCommercialContractModal: false,
        editCommercialId: null,
        commercialForm: blankForm(),

        get commercialFormAction() {
            if (this.editCommercialId && this.routes.update) {
                return `${this.routes.update}/${this.editCommercialId}`;
            }

            return this.routes.store ?? '';
        },

        openCreate() {
            this.editCommercialId = null;
            this.commercialForm = blankForm();
            this.openCommercialContractModal = true;
        },

        openEdit(id) {
            const row = this.contractsById[id];

            if (!row || !row.can_update) {
                return;
            }

            this.editCommercialId = id;
            this.commercialForm = {
                start_date: row.start_date ?? today,
                end_date: row.end_date ?? '',
                work_type: row.work_type ?? 'hourly',
                business_amount: row.business_amount ?? '',
                courier_amount: row.courier_amount ?? '',
                guaranteed_hourly_package_fee: row.guaranteed_hourly_package_fee ?? '',
                guaranteed_package_count: row.guaranteed_package_count ?? '',
                payment_period: row.payment_period ?? 'monthly',
                notes: row.notes ?? '',
            };
            this.openCommercialContractModal = true;
        },

        closeCommercialContractModal() {
            this.openCommercialContractModal = false;
            this.editCommercialId = null;
            this.commercialForm = blankForm();
        },

        get commercialNetProfit() {
            const a = parseFloat(this.commercialForm.business_amount);
            const b = parseFloat(this.commercialForm.courier_amount);

            if (Number.isNaN(a) || Number.isNaN(b)) {
                return '';
            }

            return (a - b).toFixed(2);
        },
    };
});

Alpine.data('contractPage', (preset = {}) => {
    const lockedBusinessId = lockedPresetId(preset, 'businessId');
    const contractsById = preset.contractsById ?? {};
    const routes = preset.routes ?? {};

    return {
    lockedBusinessId,
    contractsById,
    routes,
    editId: null,
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

    get formAction() {
        if (this.editId && this.routes.update) {
            return `${this.routes.update}/${this.editId}`;
        }

        return this.routes.store ?? '';
    },

    closeModal() {
        this.openModal = false;
        this.modalErrors = {};
        this.submitting = false;
        this.editId = null;
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

    openEdit(id) {
        const row = this.contractsById[id];

        if (!row) {
            return;
        }

        this.editId = id;
        this.modal = {
            business_id: String(row.business_id ?? this.lockedBusinessId ?? ''),
            contract_number: row.contract_number ?? '',
            contract_type: row.contract_type ?? '',
            start_date: row.start_date ?? '',
            end_date: row.end_date ?? '',
            notes: row.notes ?? '',
            status: row.stored_status === 'draft' ? 'draft' : 'active',
        };
        this.openModal = true;
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

Alpine.data('agencyEarningPage', (preset = {}) => ({
    activeModal: preset.openBulk ? 'bulk' : null,
    singleSaved: false,
    singleErrors: {},
    single: {
        agency_id: '',
        work_date: todayDateInput(),
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
        this.singleErrors = {};
        this.resetSingle();
    },

    resetSingle() {
        this.single = {
            agency_id: '',
            work_date: todayDateInput(),
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

        if (!this.single.work_date) {
            this.singleErrors.work_date = 'Hakediş tarihi seçilmelidir.';
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
}));

Alpine.data('agencyDocumentPage', (preset = {}) => {
    const lockedAgencyId = lockedPresetId(preset, 'agencyId');

    return {
    lockedAgencyId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    selectedFileName: '',
    maxSizeMb: preset.maxSizeMb ?? 250,
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

        if (file && this.maxSizeMb && file.size > this.maxSizeMb * 1024 * 1024) {
            this.modalErrors.file = `Dosya boyutu en fazla ${this.maxSizeMb} MB olabilir.`;
            this.selectedFileName = '';
            event.target.value = '';
        }
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

Alpine.data('earningPage', (preset = {}) => ({
    activeModal: preset.openBulk ? 'bulk' : null,
    submitting: false,
    singleErrors: {},
    editId: null,
    earningsById: preset.earningsById ?? {},
    routes: preset.routes ?? {},
    single: {
        business_id: '',
        courier_id: '',
        work_date: todayDateInput(),
        pricing_model: 'per_package',
        package_count: '',
        worked_hours: '',
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
        this.singleErrors = {};
        this.editId = null;
        this.resetSingle();
    },

    resetSingle() {
        this.single = {
            business_id: '',
            courier_id: '',
            work_date: todayDateInput(),
            pricing_model: 'per_package',
            package_count: '',
            worked_hours: '',
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

    openEdit(id) {
        const row = this.earningsById[id];

        if (!row) {
            return;
        }

        const fallbackDate = row.period_year && row.period_month
            ? `${row.period_year}-${String(row.period_month).padStart(2, '0')}-01`
            : todayDateInput();

        this.editId = id;
        this.single = {
            business_id: String(row.business_id ?? ''),
            courier_id: String(row.courier_id ?? ''),
            work_date: row.work_date || fallbackDate,
            pricing_model: row.pricing_model ?? 'per_package',
            package_count: row.package_count ?? '',
            worked_hours: row.worked_hours ?? '',
            revenue_unit_price: row.revenue_unit_price ?? '',
            courier_unit_price: row.courier_unit_price ?? '',
            revenue_total: row.revenue ?? '',
            courier_payment: row.courier_payment ?? '',
            extra_income: row.extra_income ?? 0,
            extra_expense: row.extra_expense ?? 0,
            deduction: row.deduction ?? 0,
            description: row.description ?? '',
        };
        this.activeModal = 'single';
    },

    handleRowAction(detail) {
        if (!detail?.action || !detail?.id) {
            return;
        }

        if (detail.confirm && !window.confirm(detail.confirm)) {
            return;
        }

        if (detail.action === 'edit') {
            this.openEdit(detail.id);

            return;
        }

        if (detail.action === 'approve') {
            const form = this.$refs.approveForm;

            if (!form) {
                return;
            }

            form.action = `${this.routes.approve}/${detail.id}/onayla`;
            form.submit();

            return;
        }

        if (detail.action === 'delete') {
            const form = this.$refs.deleteForm;

            if (!form) {
                return;
            }

            form.action = `${this.routes.destroy}/${detail.id}`;
            form.submit();
        }
    },

    calcSingle() {
        const s = this.single;
        let revenue = 0;
        let courier = 0;

        if (s.pricing_model === 'per_package') {
            revenue = (parseFloat(s.package_count) || 0) * (parseFloat(s.revenue_unit_price) || 0);
            courier = (parseFloat(s.package_count) || 0) * (parseFloat(s.courier_unit_price) || 0);
        } else if (s.pricing_model === 'hourly') {
            const hours = parseFloat(s.worked_hours) || 0;
            revenue = hours * (parseFloat(s.revenue_unit_price) || 0);
            courier = hours * (parseFloat(s.courier_unit_price) || 0);
        } else {
            revenue = parseFloat(s.revenue_total) || 0;
            courier = parseFloat(s.courier_payment) || 0;
        }

        const extraIncome = parseFloat(s.extra_income) || 0;
        const extraExpense = parseFloat(s.extra_expense) || 0;
        const deduction = parseFloat(s.deduction) || 0;
        const expense = courier + extraExpense;
        const profit = revenue - courier - extraExpense + extraIncome - deduction;

        return { revenue, courier, expense, profit };
    },

    formatMoney(amount) {
        return formatMoneyExclVat(amount);
    },

    validateSingle() {
        this.singleErrors = {};

        if (!this.single.business_id) this.singleErrors.business_id = 'İşletme seçilmelidir.';
        if (!this.single.courier_id) this.singleErrors.courier_id = 'Kurye seçilmelidir.';
        if (!this.single.work_date) this.singleErrors.work_date = 'Hakediş tarihi seçilmelidir.';

        if (this.single.pricing_model === 'per_package') {
            if (!this.single.package_count || parseFloat(this.single.package_count) <= 0) {
                this.singleErrors.package_count = 'Paket sayısı girilmelidir.';
            }
            if (this.single.revenue_unit_price === '' || this.single.revenue_unit_price === null) {
                this.singleErrors.revenue_unit_price = 'İşletme paket ücreti girilmelidir.';
            }
            if (this.single.courier_unit_price === '' || this.single.courier_unit_price === null) {
                this.singleErrors.courier_unit_price = 'Kurye paket ücreti girilmelidir.';
            }
        }

        if (this.single.pricing_model === 'hourly') {
            if (!this.single.worked_hours || parseFloat(this.single.worked_hours) <= 0) {
                this.singleErrors.worked_hours = 'Çalışılan saat girilmelidir.';
            }
            if (this.single.revenue_unit_price === '' || this.single.revenue_unit_price === null) {
                this.singleErrors.revenue_unit_price = 'İşletme saatlik ücreti girilmelidir.';
            }
            if (this.single.courier_unit_price === '' || this.single.courier_unit_price === null) {
                this.singleErrors.courier_unit_price = 'Kurye saatlik ücreti girilmelidir.';
            }
        }

        return Object.keys(this.singleErrors).length === 0;
    },

    handleSingleSubmit(event) {
        if (!this.validateSingle()) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
}));

Alpine.data('courierEarningPage', (preset = {}) => ({
    activeModal: preset.openBulk ? 'bulk' : null,
    singleSaved: false,
    singleErrors: {},
    single: {
        courier_id: '',
        business_id: '',
        work_date: todayDateInput(),
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
        this.singleErrors = {};
        this.resetSingle();
    },

    resetSingle() {
        this.single = {
            courier_id: '',
            business_id: '',
            work_date: todayDateInput(),
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

        if (!this.single.work_date) {
            this.singleErrors.work_date = 'Hakediş tarihi seçilmelidir.';
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
}));

Alpine.data('documentPage', (preset = {}) => {
    const lockedBusinessId = lockedPresetId(preset, 'businessId');

    return {
    lockedBusinessId,
    openModal: false,
    modalErrors: {},
    submitting: false,
    selectedFileName: '',
    maxSizeMb: preset.maxSizeMb ?? 250,
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

        if (file && this.maxSizeMb && file.size > this.maxSizeMb * 1024 * 1024) {
            this.modalErrors.file = `Dosya boyutu en fazla ${this.maxSizeMb} MB olabilir.`;
            this.selectedFileName = '';
            event.target.value = '';
        }
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

        const text = (value) => String(value ?? '').trim();

        if (!text(this.form.first_name)) {
            this.errors.first_name = 'Ad zorunludur.';
        }

        if (!text(this.form.last_name)) {
            this.errors.last_name = 'Soyad zorunludur.';
        }

        if (!text(this.form.phone)) {
            this.errors.phone = 'Telefon zorunludur.';
        }

        const email = text(this.form.email);

        if (!email) {
            this.errors.email = 'E-posta zorunludur.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            this.errors.email = 'Geçerli bir e-posta adresi girin.';
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
    maxSizeMb: preset.maxSizeMb ?? 250,
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

        if (file && this.maxSizeMb && file.size > this.maxSizeMb * 1024 * 1024) {
            this.modalErrors.file = `Dosya boyutu en fazla ${this.maxSizeMb} MB olabilir.`;
            this.selectedFileName = '';
            event.target.value = '';
        }
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

Alpine.data('financeCurrentAccountPage', (preset = {}) => ({
    accountDetails: preset.accountDetails ?? preset,
    routes: preset.routes ?? {},
    activeModal: null,
    selected: null,
    editAccountId: null,
    editAccount: {
        type: 'business',
        title: '',
        phone: '',
        email: '',
        tax_number: '',
        city: '',
        address: '',
        status: 'active',
        entity_id: null,
    },
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

    openEditAccount(id) {
        const account = this.accountDetails[id] ?? this.accountDetails[String(id)];

        if (!account) {
            return;
        }

        this.editAccountId = id;
        this.editAccount = {
            type: account.type ?? account.entity_type ?? 'business',
            title: account.title === '—' ? '' : (account.title ?? ''),
            phone: account.phone === '—' ? '' : (account.phone ?? ''),
            email: account.email ?? '',
            tax_number: account.tax_number ?? '',
            city: account.city === '—' ? '' : (account.city ?? ''),
            address: account.address ?? '',
            status: account.status ?? 'active',
            entity_id: account.entity_id ?? null,
        };
        this.activeModal = 'edit-account';
    },

    closeModals() {
        this.activeModal = null;
        this.selected = null;
        this.editAccountId = null;
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
        if (detail?.action === 'edit' && detail?.id) {
            this.openEditAccount(detail.id);

            return;
        }

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
    errors: {},
    remainingAmount: 0,
    selectedIds: [],
    form: {
        business_id: '',
        revenue_id: '',
        invoice_no: '',
        collection_date: new Date().toISOString().slice(0, 10),
        due_date: '',
        total_amount: '',
        collected_amount: '',
        payment_method: '',
        payment_reference: '',
        bank: '',
        description: '',
    },
    bulk: {
        collection_date: new Date().toISOString().slice(0, 10),
        payment_method: 'bank_transfer',
    },

    closeModals() {
        this.activeModal = null;
        this.saved = false;
        this.errors = {};
        this.resetForm();
    },

    toggleSelect(id) {
        const value = Number(id);
        if (this.selectedIds.includes(value)) {
            this.selectedIds = this.selectedIds.filter((item) => item !== value);
        } else {
            this.selectedIds.push(value);
        }
    },

    toggleSelectAll(ids) {
        const values = ids.map(Number);
        const allSelected = values.every((id) => this.selectedIds.includes(id));
        this.selectedIds = allSelected
            ? this.selectedIds.filter((id) => !values.includes(id))
            : Array.from(new Set([...this.selectedIds, ...values]));
    },

    isSelected(id) {
        return this.selectedIds.includes(Number(id));
    },

    resetForm() {
        this.form = {
            business_id: '',
            revenue_id: '',
            invoice_no: '',
            collection_date: new Date().toISOString().slice(0, 10),
            due_date: '',
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
    errors: {},
    remainingAmount: 0,
    selectedIds: [],
    recipientsByType,
    form: {
        recipient_type: '',
        recipient_id: '',
        earning_id: '',
        payment_date: new Date().toISOString().slice(0, 10),
        total_amount: '',
        paid_amount: '',
        payment_method: '',
        bank_account: '',
        payment_reference: '',
        description: '',
    },
    bulk: {
        payment_date: new Date().toISOString().slice(0, 10),
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
        this.errors = {};
        this.resetForm();
    },

    toggleSelect(id) {
        const value = Number(id);
        if (this.selectedIds.includes(value)) {
            this.selectedIds = this.selectedIds.filter((item) => item !== value);
        } else {
            this.selectedIds.push(value);
        }
    },

    toggleSelectAll(ids) {
        const values = ids.map(Number);
        const allSelected = values.every((id) => this.selectedIds.includes(id));
        this.selectedIds = allSelected
            ? this.selectedIds.filter((id) => !values.includes(id))
            : Array.from(new Set([...this.selectedIds, ...values]));
    },

    isSelected(id) {
        return this.selectedIds.includes(Number(id));
    },

    resetForm() {
        this.form = {
            recipient_type: '',
            recipient_id: '',
            earning_id: '',
            payment_date: new Date().toISOString().slice(0, 10),
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
    errors: {},
    vatAmount: 0,
    grandTotal: 0,
    form: {
        business_id: '',
        earning_id: '',
        invoice_type: 'e_invoice',
        invoice_date: new Date().toISOString().slice(0, 10),
        due_date: '',
        subtotal: '',
        vat_rate: 20,
        description: '',
    },
    bulk: {
        invoice_type: 'e_invoice',
        invoice_date: new Date().toISOString().slice(0, 10),
        vat_rate: 20,
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
            earning_id: '',
            invoice_type: 'e_invoice',
            invoice_date: new Date().toISOString().slice(0, 10),
            due_date: '',
            subtotal: '',
            vat_rate: 20,
            description: '',
        };
        this.vatAmount = 0;
        this.grandTotal = 0;
    },

    calcTotals() {
        const subtotal = parseFloat(this.form.subtotal) || 0;
        const rate = parseFloat(this.form.vat_rate) || 0;
        this.vatAmount = Math.round(subtotal * (rate / 100) * 100) / 100;
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

        if (!this.form.invoice_date) {
            this.errors.invoice_date = 'Fatura tarihi girilmelidir.';
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

Alpine.data('userManagementPage', (preset = {}) => ({
    activeModal: preset.openCreate ? 'create' : null,
    routes: preset.routes ?? {},

    closeModals() {
        this.activeModal = null;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;

            return;
        }

        if (detail?.action === 'reset-password' && detail?.id) {
            if (detail.confirm && !window.confirm(detail.confirm)) {
                return;
            }

            const form = this.$refs.resetPasswordForm;

            if (!form) {
                return;
            }

            form.action = `${this.routes.resetPassword}/${detail.id}/sifre-sifirla`;
            form.submit();
        }
    },
}));

Alpine.data('roleManagementPage', (preset = {}) => ({
    activeModal: preset.openCreate ? 'create' : null,

    closeModals() {
        this.activeModal = null;
    },

    handleRowAction(detail) {
        if (detail?.modal) {
            this.activeModal = detail.modal;
        }
    },
}));

Alpine.data('permissionManagementPage', (rolesPayload = {}, initialRole = 'general_manager', summary = {}, saveUrl = '') => ({
    rolesPayload,
    selectedRole: initialRole,
    previousRole: initialRole,
    saveUrl,
    matrix: [],
    defaults: [],
    isLocked: false,
    dirty: false,
    saved: false,
    saving: false,
    saveError: null,
    saveMessage: null,
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
                if (action?.applicable && action.granted) {
                    slugs.push(...(action.slugs ?? []));
                }
            });
        });

        return [...new Set(slugs)];
    },

    async save() {
        if (this.isLocked || this.saving || !this.saveUrl) {
            return;
        }

        this.saving = true;
        this.saveError = null;
        this.saveMessage = null;

        try {
            const response = await fetch(this.saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({
                    role: this.selectedRole,
                    permissions: this.collectGrantedSlugs(),
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (! response.ok) {
                const validationMessage = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : null;

                throw new Error(validationMessage || data.message || 'Yetki değişiklikleri kaydedilemedi.');
            }

            this.rolesPayload[this.selectedRole] = data.role_payload;
            this.loadRole(this.selectedRole, false);
            this.saved = true;
            this.dirty = false;
            this.saveMessage = data.message ?? 'Yetki değişiklikleri kaydedildi.';
        } catch (error) {
            this.saved = false;
            this.saveError = error?.message ?? 'Yetki değişiklikleri kaydedilemedi.';
        } finally {
            this.saving = false;
        }
    },

    markDirty() {
        this.dirty = true;
        this.saved = false;
        this.saveError = null;
        this.saveMessage = null;
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

Alpine.data('formBuilderListPage', (config = {}) => ({
    openStatusModal: !!config.openStatusModal,
    closeStatusModal() {
        this.openStatusModal = false;
        const url = new URL(window.location.href);
        if (url.searchParams.has('statuses')) {
            url.searchParams.delete('statuses');
            window.history.replaceState({}, '', url.pathname + (url.search ? url.search : '') + url.hash);
        }
    },
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

Alpine.data('staffAttendanceEnd', (config = {}) => ({
    openEndModal: false,
    replacementSearch: '',
    availableCouriers: config.availableCouriers || [],
    endReasons: config.endReasons || {},
    endForm: {
        business_id: '',
        attendance_id: '',
        work_date: '',
        courier_id: '',
        courier_name: '',
        ended_at: '',
        min_ended_at: '',
        shift_end_at: '',
        end_reason: '',
        replacement_courier_id: '',
        package_count: '',
        pricing_model: '',
        notes: '',
        return_to: '',
        week: '',
    },
    openEndAttendance(payload = {}) {
        const nowLocal = this.toDateTimeLocal(new Date());
        const minEnded = payload.started_at || payload.shift_start_at || '';
        let endedAt = payload.ended_at || nowLocal;
        if (payload.shift_end_at && endedAt > payload.shift_end_at) {
            endedAt = payload.shift_end_at;
        }
        if (minEnded && endedAt < minEnded) {
            endedAt = minEnded;
        }

        this.replacementSearch = '';
        this.endForm = {
            business_id: String(payload.business_id || ''),
            attendance_id: String(payload.attendance_id || ''),
            work_date: payload.work_date || '',
            courier_id: String(payload.courier_id || ''),
            courier_name: payload.courier_name || '',
            ended_at: endedAt,
            min_ended_at: minEnded,
            shift_end_at: payload.shift_end_at || '',
            end_reason: '',
            replacement_courier_id: '',
            package_count: '',
            pricing_model: payload.pricing_model || '',
            notes: '',
            return_to: payload.return_to || '',
            week: payload.week || '',
        };
        this.openEndModal = true;
    },
    closeEndModal() {
        this.openEndModal = false;
    },
    needsEndReason() {
        if (this.endForm.replacement_courier_id) {
            return true;
        }
        if (this.endForm.ended_at && this.endForm.shift_end_at && this.endForm.ended_at < this.endForm.shift_end_at) {
            return true;
        }
        return false;
    },
    filteredReplacementCouriers() {
        const excludeId = String(this.endForm.courier_id || '');
        const needle = String(this.replacementSearch || '').trim().toLocaleLowerCase('tr-TR');

        return (this.availableCouriers || []).filter((courier) => {
            if (String(courier.id) === excludeId) {
                return false;
            }
            if (!needle) {
                return true;
            }
            const name = String(courier.name || '').toLocaleLowerCase('tr-TR');
            const phone = String(courier.phone || '').toLocaleLowerCase('tr-TR');
            return name.includes(needle) || phone.includes(needle);
        });
    },
    toDateTimeLocal(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    },
}));

Alpine.data('shiftPlanningPage', (config = {}) => ({
    openShiftModal: false,
    openDeleteModal: false,
    openEndModal: false,
    replacementSearch: '',
    shiftMode: 'create',
    selectedBusinessId: config.selectedBusinessId,
    shifts: config.shifts || [],
    availableCouriers: config.availableCouriers || [],
    endReasons: config.endReasons || {},
    eligibleCouriers: config.availableCouriers || [],
    eligibleCouriersLoading: false,
    courierSearch: '',
    eligibleFetchToken: 0,
    canCreate: config.canCreate,
    canUpdate: config.canUpdate,
    canDelete: config.canDelete,
    defaultStartDate: config.defaultStartDate || '',
    defaultEndDate: config.defaultEndDate || '',
    storeUrl: config.storeUrl,
    updateUrlTemplate: config.updateUrlTemplate,
    destroyUrlTemplate: config.destroyUrlTemplate,
    eligibleCouriersUrl: config.eligibleCouriersUrl || '',
    deleteShiftId: null,
    endForm: {
        business_id: '',
        attendance_id: '',
        work_date: '',
        courier_id: '',
        courier_name: '',
        ended_at: '',
        min_ended_at: '',
        shift_end_at: '',
        end_reason: '',
        replacement_courier_id: '',
        package_count: '',
        pricing_model: '',
        notes: '',
        return_to: '',
        week: '',
    },
    shiftForm: {
        id: null,
        start_time: '09:00',
        end_time: '17:00',
        start_date: config.defaultStartDate || '',
        end_date: config.defaultEndDate || '',
        required_headcount: 1,
        notes: '',
        is_active: true,
        courier_ids: [],
    },
    init() {
        this.$watch(
            () => [
                this.openShiftModal,
                this.shiftMode,
                this.shiftForm.id,
                this.shiftForm.start_date,
                this.shiftForm.end_date,
                this.shiftForm.start_time,
                this.shiftForm.end_time,
            ].join('|'),
            () => {
                if (! this.openShiftModal) {
                    return;
                }

                this.refreshEligibleCouriers({
                    start_date: this.shiftForm.start_date,
                    end_date: this.shiftForm.end_date,
                    start_time: this.shiftForm.start_time,
                    end_time: this.shiftForm.end_time,
                    exclude_shift_id: this.shiftMode === 'edit' ? this.shiftForm.id : null,
                });
            },
        );

        this.$watch('shiftForm.start_date', (startDate) => {
            if (!startDate) {
                return;
            }

            if (!this.shiftForm.end_date || this.shiftForm.end_date < startDate) {
                this.shiftForm.end_date = startDate;
            }
        });
    },
    openCreate() {
        this.shiftMode = 'create';
        this.courierSearch = '';
        this.shiftForm = {
            id: null,
            start_time: '09:00',
            end_time: '17:00',
            start_date: this.defaultStartDate,
            end_date: this.defaultEndDate,
            required_headcount: 1,
            notes: '',
            is_active: true,
            courier_ids: [],
        };
        this.openShiftModal = true;
        this.refreshEligibleCouriers({
            start_date: this.shiftForm.start_date,
            end_date: this.shiftForm.end_date,
            start_time: this.shiftForm.start_time,
            end_time: this.shiftForm.end_time,
        });
    },
    openEdit(id) {
        const shift = this.shifts.find((item) => item.id === id);
        if (!shift) return;
        this.shiftMode = 'edit';
        this.courierSearch = '';
        this.shiftForm = {
            id: shift.id,
            start_time: shift.start_time,
            end_time: shift.end_time,
            start_date: shift.start_date || this.defaultStartDate,
            end_date: shift.end_date || this.defaultEndDate,
            required_headcount: shift.required_headcount || 1,
            notes: shift.notes || '',
            is_active: !!shift.is_active,
            courier_ids: [...(shift.courier_ids || [])].map(String),
        };
        this.openShiftModal = true;
        this.refreshEligibleCouriers({
            start_date: this.shiftForm.start_date,
            end_date: this.shiftForm.end_date,
            start_time: this.shiftForm.start_time,
            end_time: this.shiftForm.end_time,
            exclude_shift_id: shift.id,
        });
    },
    openDeleteConfirm(id) {
        this.deleteShiftId = id;
        this.openDeleteModal = true;
    },
    closeShiftModal() {
        this.openShiftModal = false;
    },
    closeDeleteModal() {
        this.openDeleteModal = false;
        this.deleteShiftId = null;
    },
    openEndAttendance(payload = {}) {
        const nowLocal = this.toDateTimeLocal(new Date());
        const minEnded = payload.started_at || payload.shift_start_at || '';
        let endedAt = payload.ended_at || nowLocal;
        if (payload.shift_end_at && endedAt > payload.shift_end_at) {
            endedAt = payload.shift_end_at;
        }
        if (minEnded && endedAt < minEnded) {
            endedAt = minEnded;
        }

        this.replacementSearch = '';
        this.endForm = {
            business_id: String(payload.business_id || ''),
            attendance_id: String(payload.attendance_id || ''),
            work_date: payload.work_date || '',
            courier_id: String(payload.courier_id || ''),
            courier_name: payload.courier_name || '',
            ended_at: endedAt,
            min_ended_at: minEnded,
            shift_end_at: payload.shift_end_at || '',
            end_reason: '',
            replacement_courier_id: '',
            package_count: '',
            pricing_model: payload.pricing_model || '',
            notes: '',
            return_to: payload.return_to || '',
            week: payload.week || '',
        };
        this.openEndModal = true;
    },
    closeEndModal() {
        this.openEndModal = false;
    },
    needsEndReason() {
        if (this.endForm.replacement_courier_id) {
            return true;
        }
        if (this.endForm.ended_at && this.endForm.shift_end_at && this.endForm.ended_at < this.endForm.shift_end_at) {
            return true;
        }
        return false;
    },
    filteredReplacementCouriers() {
        const excludeId = String(this.endForm.courier_id || '');
        const needle = String(this.replacementSearch || '').trim().toLocaleLowerCase('tr-TR');

        return (this.availableCouriers || []).filter((courier) => {
            if (String(courier.id) === excludeId) {
                return false;
            }
            if (!needle) {
                return true;
            }
            const name = String(courier.name || '').toLocaleLowerCase('tr-TR');
            const phone = String(courier.phone || '').toLocaleLowerCase('tr-TR');
            return name.includes(needle) || phone.includes(needle);
        });
    },
    toDateTimeLocal(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    },
    async refreshEligibleCouriers(schedule) {
        if (!this.eligibleCouriersUrl) {
            this.eligibleCouriers = this.availableCouriers || [];
            return;
        }

        const startDate = schedule.start_date || '';
        const endDate = schedule.end_date || '';
        const startTime = (schedule.start_time || '').slice(0, 5);
        const endTime = (schedule.end_time || '').slice(0, 5);

        if (!startDate || !endDate || !startTime || !endTime) {
            this.eligibleCouriers = this.availableCouriers || [];
            return;
        }

        const token = ++this.eligibleFetchToken;
        this.eligibleCouriersLoading = true;

        try {
            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate,
                start_time: startTime,
                end_time: endTime,
            });

            if (schedule.exclude_shift_id) {
                params.set('exclude_shift_id', String(schedule.exclude_shift_id));
            }

            const response = await fetch(`${this.eligibleCouriersUrl}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('eligible couriers failed');
            }

            const payload = await response.json();
            if (token !== this.eligibleFetchToken) {
                return;
            }

            this.eligibleCouriers = payload.couriers || [];
            this.pruneSelectedCouriers();
        } catch (error) {
            if (token !== this.eligibleFetchToken) {
                return;
            }
            this.eligibleCouriers = this.availableCouriers || [];
        } finally {
            if (token === this.eligibleFetchToken) {
                this.eligibleCouriersLoading = false;
            }
        }
    },
    pruneSelectedCouriers() {
        const allowed = new Set((this.eligibleCouriers || []).map((courier) => String(courier.id)));
        this.shiftForm.courier_ids = (this.shiftForm.courier_ids || [])
            .map(String)
            .filter((id) => allowed.has(id));
    },
    filteredCreateCouriers() {
        return this.filterCouriersBySearch(this.eligibleCouriers, this.courierSearch);
    },
    filterCouriersBySearch(couriers, query) {
        const needle = String(query || '').trim().toLocaleLowerCase('tr-TR');
        if (!needle) {
            return couriers || [];
        }

        return (couriers || []).filter((courier) => {
            const name = String(courier.name || '').toLocaleLowerCase('tr-TR');
            const phone = String(courier.phone || '').toLocaleLowerCase('tr-TR');
            return name.includes(needle) || phone.includes(needle);
        });
    },
    shiftFormAction() {
        if (this.shiftMode === 'edit' && this.shiftForm.id) {
            return this.updateUrlTemplate.replace('__ID__', this.shiftForm.id);
        }
        return this.storeUrl;
    },
    destroyFormAction() {
        return this.destroyUrlTemplate.replace('__ID__', this.deleteShiftId);
    },
}));

Alpine.data('globalSearch', (endpoint) => ({
    query: '',
    panelOpen: false,
    resultsOpen: false,
    loading: false,
    groups: [],
    total: 0,
    endpoint,

    togglePanel() {
        this.panelOpen = ! this.panelOpen;

        if (this.panelOpen) {
            this.$nextTick(() => this.$refs.input?.focus());
        } else {
            this.resetResults();
        }
    },

    closePanel() {
        this.panelOpen = false;
        this.resetResults();
    },

    resetResults() {
        this.resultsOpen = false;
        this.groups = [];
        this.total = 0;
        this.loading = false;
    },

    async search() {
        const q = this.query.trim();

        if (q.length < 2) {
            this.groups = [];
            this.total = 0;
            this.resultsOpen = false;
            return;
        }

        this.loading = true;
        this.resultsOpen = true;

        try {
            const response = await fetch(`${this.endpoint}?q=${encodeURIComponent(q)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                throw new Error('search failed');
            }

            const payload = await response.json();
            this.groups = payload.data?.groups ?? [];
            this.total = payload.data?.total ?? 0;
        } catch (error) {
            this.groups = [];
            this.total = 0;
        } finally {
            this.loading = false;
        }
    },

    close() {
        this.closePanel();
    },
}));

Alpine.start();
