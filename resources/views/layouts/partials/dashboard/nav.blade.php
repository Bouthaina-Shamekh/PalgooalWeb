<nav class="pc-sidebar">
    <div class="navbar-wrapper">
        <div class="m-header flex items-center py-4 px-6 h-header-height">
            <a href="../dashboard/index.html" class="b-brand flex items-center gap-3">
                <!-- ========   Change your logo from here   ============ -->
                <img src="{{ asset('assets-dashboard/images/logo-dark.svg') }}" class="img-fluid logo-lg" alt="logo" />
            </a>
        </div>
        <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
            <div
                class="card pc-user-card mx-[15px] mb-[15px] bg-theme-sidebaruserbg dark:bg-themedark-sidebaruserbg border border-secondary-100/10">
                <div class="card-body !p-4">
                    <div class="flex items-center gap-3">
                        <img class="shrink-0 w-[48px] h-[48px] rounded-full border border-secondary-200"
                            src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}" alt="user-image" />
                        <div class="grow">
                            <h6 class="mb-0 text-sm font-semibold">{{ Auth::user()->name }}</h6>
                            <small class="text-xs text-muted">{{ Auth::user()->email }}</small>
                        </div>
                        <button class="shrink-0 btn btn-icon inline-flex btn-link-secondary" data-pc-toggle="collapse"
                            aria-expanded="false" aria-controls="pc_sidebar_userlink">
                            <svg class="pc-icon w-[20px] h-[20px]">
                                <use xlink:href="#custom-more-vertical"></use>
                            </svg>
                        </button>
                    </div>
                    <div class="hidden pc-user-links mt-3" id="pc_sidebar_userlink">
                        <div class="space-y-2">
                            <a href="{{ route('dashboard.users.profile', Auth::user()->id) }}"
                                class="flex items-center gap-2 px-2 py-2 rounded hover:bg-primary-50">
                                <i class="text-lg leading-none ti ti-user"></i>
                                <span class="text-sm">{{ t('dashboard.My_Account', 'My Account') }}</span>
                            </a>
                            <a href="#!" class="flex items-center gap-2 px-2 py-2 rounded hover:bg-primary-50">
                                <i class="text-lg leading-none ti ti-settings"></i>
                                <span class="text-sm">{{ t('dashboard.Settings', 'Settings') }}</span>
                            </a>
                            <form action="{{ route('logout') }}" method="post" class="px-2">
                                @csrf
                                <button type="submit"
                                    class="w-full btn btn-outline-danger flex items-center justify-center gap-2">
                                    <i class="text-lg leading-none ti ti-power"></i>
                                    <span class="text-sm">{{ t('dashboard.Logout', 'Logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <ul class="pc-navbar">
                <li class="pc-item pc-caption">
                    <label
                        class="text-xs uppercase tracking-wider text-muted">{{ t('dashboard.Navigation', 'Navigation') }}</label>
                </li>
                <li class="pc-item">
                    <a href="{{ route('dashboard.home') }}" class="pc-link">
                        <span class="pc-micon">
                            <span class="pc-micon">
                                <i class="fas fa-home"></i>
                            </span>
                        </span>
                        <span class="pc-mtext">{{ t('dashboard.Home', 'Home') }}</span>
                    </a>
                </li>

                @can('view', 'App\\Models\\Page')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.pages') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-file"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.Pages', 'Pages') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\Template')
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link">
                            <span class="pc-micon">
                                <svg class="pc-icon">
                                    <use xlink:href="#custom-layer"></use>
                                </svg>
                            </span>
                            <span class="pc-mtext"
                                data-i18n="Online Courses">{{ t('dashboard.Template_management', 'Template management') }}</span>
                            <span class="pc-arrow"><i data-feather="chevron-left" class="rtl:rotate-180"></i></span>
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="{{ route('dashboard.templates.index') }}"
                                    data-i18n="Menus">{{ t('dashboard.All_templates', 'All templates') }}</a></li>
                            <li class="pc-item"><a class="pc-link" href="{{ route('dashboard.category') }}"
                                    data-i18n="Menus">{{ t('dashboard.Categories', 'Categories') }}</a></li>
                            <li class="pc-item"><a class="pc-link" href="{{ route('dashboard.reviews.index') }}"
                                    data-i18n="Menus">{{ t('dashboard.reviews', 'reviews') }}</a></li>
                        </ul>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Service')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.services.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-briefcase"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.services', 'services') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\Feedback')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.feedbacks.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-star"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.feedbacks', 'testimonial') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Portfolio')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.portfolios.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-briefcase"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.portfolios', 'portfolios') }}</span>
                        </a>
                    </li>
                @endcan
                <li class="pc-item pc-caption">
                    <label
                        class="text-xs uppercase tracking-wider text-muted">{{ t('dashboard.clients', 'clients') }}</label>
                </li>
                @can('view', 'App\\Models\\Client')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.clients') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-users"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.clients', 'clients') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Domain')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.domains.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-globe"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.domains', 'domains') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Plan')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.plans.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-boxes"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.plans', 'plans') }}</span>
                        </a>
                    </li>
                    <li class="pc-item">
                        <a href="{{ route('dashboard.plan_categories.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-boxes"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.plan-categories', 'plan categories') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Subscription')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.subscriptions.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-money-bill"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.subscriptions', 'subscriptions') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Invoice')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.invoices.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.invoices', 'invoices') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Order')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.orders.index') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-server"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.orders', 'orders') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Settings-crm')
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link">
                            <span class="pc-micon">
                                <i class="fas fa-users"></i>
                            </span>
                            <span class="pc-mtext">
                               {{ t('dashboard.CRM_management', 'CRM management') }}
                            </span>
                            @if (App::getLocale() == 'en')
                                <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
                            @else
                                <span class="pc-arrow"><i data-feather="chevron-left"></i></span>
                            @endif
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item">
                                <a class="pc-link" href="{{ route('dashboard.servers.index') }}">
                                    {{ t('dashboard.servers', 'servers') }}
                                </a>
                            </li>
                            <li class="pc-item">
                                <a class="pc-link" href="{{ route('dashboard.domain_providers.index') }}">
                                    {{ t('dashboard.domain_providers', 'domain providers') }}
                                </a>
                            </li>
                            <li class="pc-item">
                                <a class="pc-link" href="{{ route('dashboard.domain_tlds.index') }}">
                                    {{ t('dashboard.domain-tlds', 'domain tlds') }}
                                </a>
                            </li>
                            <li class="pc-item">
                                <a class="pc-link" href="{{ route('dashboard.subscriptions.sync-logs') }}">
                                    {{ t('dashboard.sync-logs', 'sync-logs') }}
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                <li class="pc-item pc-caption">
                    <label>{{ t('dashboard.Site_settings', 'Site settings') }}</label>
                    <svg class="pc-icon">
                        <use xlink:href="#custom-presentation-chart"></use>
                    </svg>
                </li>
                @can('view', 'App\\Models\\Media')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.media') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-images"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.media', 'Media') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', 'App\\Models\\Header')
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link">
                            <span class="pc-micon">
                                <svg class="pc-icon">
                                    <use xlink:href="#custom-layer"></use>
                                </svg>
                            </span>
                            <span class="pc-mtext"
                                data-i18n="Online Courses">{{ t('dashboard.Appearance', 'Appearance') }}</span>
                            <span class="pc-arrow"><i data-feather="chevron-right" class="rtl:rotate-180"></i></span>
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item"><a class="pc-link" href="{{ route('dashboard.headers') }}"
                                    data-i18n="Menus">{{ t('dashboard.Menus', 'Menus') }}</a></li>
                            <li class="pc-item"><a class="pc-link" href="{{ route('dashboard.languages.index') }}"
                                    data-i18n="Menus">{{ t('dashboard.languages', 'languages') }}</a></li>
                        </ul>
                    </li>
                @endcan
                @can('view', 'App\\Models\\User')
                    <li class="pc-item pc-hasmenu">
                        <a href="#!" class="pc-link">
                            <span class="pc-micon">
                                <i class="fas fa-users"></i>
                            </span>
                            <span class="pc-mtext">
                                {{ __('Users') }}
                            </span>
                            @if (App::getLocale() == 'en')
                                <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
                            @else
                                <span class="pc-arrow"><i data-feather="chevron-left"></i></span>
                            @endif
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item">
                                <a class="pc-link" href="{{ route('dashboard.users.index') }}">
                                    {{ t('dashboard.Users_show', 'Users show') }}
                                </a>
                            </li>
                            <li class="pc-item">
                                <a class="pc-link" href="{{ route('dashboard.users.create') }}">
                                    {{ t('dashboard.Add_User', 'Add User') }}
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                @can('view', 'App\\Models\\GeneralSetting')
                    <li class="pc-item">
                        <a href="{{ route('dashboard.general_settings') }}" class="pc-link">
                            <span class="pc-micon">
                                <span class="pc-micon">
                                    <i class="fas fa-cog"></i>
                                </span>
                            </span>
                            <span class="pc-mtext">{{ t('dashboard.General_Setting', 'General Setting') }}</span>
                        </a>
                    </li>
                @endcan

            </ul>
        </div>
    </div>
</nav>
