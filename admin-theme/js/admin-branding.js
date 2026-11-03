document.addEventListener('DOMContentLoaded', function () {
    // 1. Inject the Branding Modal (tokens only, no bg-light/bg-white)
    const modalHTML = `
    <div class="modal fade" id="adminBrandingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-paint-brush mr-2 text-primary"></i>Edit Brand Logo</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="adminBrandingForm" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="form-group mb-4 d-none">
                            <input type="hidden" name="admin_company_name" id="modal_admin_company_name" value="">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Company Logo</label>
                            <div class="custom-file mb-2">
                                <input type="file" name="admin_logo" id="modalAdminLogoInput" class="custom-file-input" onchange="previewBrandingLogo(this)">
                                <label class="custom-file-label" for="modalAdminLogoInput">Choose new logo...</label>
                            </div>
                            <div id="modalLogoPreview" class="text-center p-3 rounded mt-2" style="background: var(--adm-surface-2); border: 1px solid var(--adm-border); min-height: 80px; display: flex; align-items: center; justify-content: center;">
                                <img src="" id="modalLogoImg" style="max-height: 60px; max-width: 100%; display: none;">
                                <div id="modalLogoPlaceholder" style="color: var(--adm-text-muted);"><i class="fa fa-image fa-2x mb-2"></i><br>New Logo Preview</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="saveBrandingBtn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // 2. Identify Brand Elements and Add Triggers (REMOVED: User requested to stop direct logo touch edit)

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
        const titleEl = document.querySelector('.brand-title');
        const currentName = titleEl ? titleEl.innerText.trim() : '';
        const currentLogo = document.querySelector('.brand-logo img') ? document.querySelector('.brand-logo img').src : '';

        const nameInput = document.getElementById('modal_admin_company_name');
        if (nameInput) {
            nameInput.value = currentName;
        }

        if (currentLogo) {
            document.getElementById('modalLogoImg').src = currentLogo;
            document.getElementById('modalLogoImg').style.display = 'block';
            document.getElementById('modalLogoPlaceholder').style.display = 'none';
        }

        $('#adminBrandingModal').modal('show');
    };

    window.previewBrandingLogo = function (input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                if (window.TitNotify) {
                    TitNotify.error('Invalid file format. Please upload JPG, PNG, or WebP.');
                }
                input.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                if (window.TitNotify) {
                    TitNotify.error('File is too large. Maximum size is 5MB.');
                }
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('modalLogoImg').src = e.target.result;
                document.getElementById('modalLogoImg').style.display = 'block';
                document.getElementById('modalLogoPlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    };

    // 5. AJAX Submission
    const form = document.getElementById('adminBrandingForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const saveBtn = document.getElementById('saveBrandingBtn');
            TitButton.setLoading(saveBtn, 'Saving...');

            const formData = new FormData(this);
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (tokenMeta) {
                formData.append('_token', tokenMeta.content);
            }

            fetch('/admin/settings/branding', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success) {
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
                        if (window.TitNotify) {
                            TitNotify.success('Branding updated successfully!');
                        }
                    } else {
                        if (window.TitNotify) {
                            TitNotify.error((data && data.message) ? data.message : 'Error updating branding');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (window.TitNotify) {
                        TitNotify.error('An error occurred while saving.');
                    }
                })
                .finally(() => {
                    TitButton.clearLoading(saveBtn);
                });
        });
    }
});
