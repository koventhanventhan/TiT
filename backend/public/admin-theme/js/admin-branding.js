document.addEventListener('DOMContentLoaded', function () {
    // 1. Inject the Branding Modal
    const modalHTML = `
    <div class="modal fade" id="adminBrandingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-paint-brush mr-2"></i>Edit Brand Logo</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="adminBrandingForm" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Company Name</label>
                            <input type="text" name="admin_company_name" id="modal_admin_company_name" class="form-control form-control-lg" placeholder="Enter company name">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Company Logo</label>
                            <div class="custom-file mb-2">
                                <input type="file" name="admin_logo" id="modalAdminLogoInput" class="custom-file-input" onchange="previewBrandingLogo(this)">
                                <label class="custom-file-label" for="modalAdminLogoInput">Choose new logo...</label>
                            </div>
                            <div id="modalLogoPreview" class="text-center p-3 bg-light rounded border mt-2" style="min-height: 80px; display: flex; align-items: center; justify-content: center;">
                                <img src="" id="modalLogoImg" style="max-height: 60px; max-width: 100%; display: none;">
                                <div id="modalLogoPlaceholder" class="text-muted"><i class="fa fa-image fa-2x mb-2"></i><br>New Logo Preview</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="saveBrandingBtn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <style>
        .branding-editable { 
            cursor: pointer; 
            position: relative; 
            transition: all 0.2s ease; 
        }
        .branding-editable:hover { 
            outline: 2px dashed #EB8153; 
            outline-offset: 4px;
            background: rgba(235, 129, 83, 0.1);
        }
        .branding-edit-indicator {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #EB8153;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 10;
        }
        .branding-editable:hover .branding-edit-indicator {
            opacity: 1;
        }
    </style>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // 2. Identify Brand Elements and Add Triggers
    const brandLogoLink = document.querySelector('.brand-logo');
    if (brandLogoLink) {
        brandLogoLink.classList.add('branding-editable');
        brandLogoLink.insertAdjacentHTML('afterbegin', '<div class="branding-edit-indicator"><i class="fa fa-pencil"></i></div>');

        brandLogoLink.addEventListener('click', function (e) {
            e.preventDefault();
            openBrandingModal();
        });
    }

    // 3. Monitor Profile Dropdown for "Branding" items
    const profileDropdown = document.querySelector('.header-profile .dropdown-item i.la-calendar');
    if (profileDropdown) {
        const calendarLink = profileDropdown.closest('.dropdown-item');
        const brandingLink = `
            <a href="javascript:void(0)" class="dropdown-item ai-icon" onclick="openBrandingModal()">
                <i class="fa fa-paint-brush text-primary mr-2"></i>
                <span class="ml-2">Brand Logo</span>
            </a>
        `;
        calendarLink.insertAdjacentHTML('afterend', brandingLink);
    } else {
        // Fallback if calendar link not found
        const dropdown = document.querySelector('.header-profile .dropdown-menu');
        if (dropdown) {
            const brandingLink = `
                <a href="javascript:void(0)" class="dropdown-item ai-icon" onclick="openBrandingModal()">
                    <i class="fa fa-paint-brush text-primary mr-2"></i>
                    <span class="ml-2">Brand Logo</span>
                </a>
            `;
            dropdown.insertAdjacentHTML('beforeend', brandingLink);
        }
    }

    // 4. Modal Functions
    window.openBrandingModal = function () {
        const currentName = document.querySelector('.brand-title').innerText.trim();
        const currentLogo = document.querySelector('.brand-logo img') ? document.querySelector('.brand-logo img').src : '';

        document.getElementById('modal_admin_company_name').value = currentName === 'Zenix' ? '' : currentName;

        if (currentLogo) {
            document.getElementById('modalLogoImg').src = currentLogo;
            document.getElementById('modalLogoImg').style.display = 'block';
            document.getElementById('modalLogoPlaceholder').style.display = 'none';
        }

        $('#adminBrandingModal').modal('show');
    };

    window.previewBrandingLogo = function (input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('modalLogoImg').src = e.target.result;
                document.getElementById('modalLogoImg').style.display = 'block';
                document.getElementById('modalLogoPlaceholder').style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    };

    // 5. AJAX Submission
    const form = document.getElementById('adminBrandingForm');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const saveBtn = document.getElementById('saveBrandingBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';

        const formData = new FormData(this);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '');

        fetch('/admin/settings/branding', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update Sidebar Logo/Name
                    const brandTitles = document.querySelectorAll('.brand-title');
                    brandTitles.forEach(el => el.innerText = data.name);

                    const brandLogos = document.querySelectorAll('.brand-logo');
                    brandLogos.forEach(el => {
                        if (data.logo_url) {
                            let img = el.querySelector('img');
                            if (!img) {
                                el.querySelector('svg')?.remove();
                                img = document.createElement('img');
                                img.style.maxHeight = '45px';
                                img.style.maxWidth = '45px';
                                img.style.objectFit = 'contain';
                                el.prepend(img);
                            }
                            img.src = data.logo_url;
                        }
                    });

                    $('#adminBrandingModal').modal('hide');
                    alert('Branding updated successfully!');
                } else {
                    alert(data.message || 'Error updating branding');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving.');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Save Changes';
            });
    });
});
