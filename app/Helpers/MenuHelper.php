<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class MenuHelper
{
    public static function getMenuGroups()
    {
        $user = Auth::user();

        if (!$user) {
            return [['title' => 'Menu', 'items' => []]];
        }

        // Super Admin
        if ($user->hasRole('super_admin')) {
            return [
                [
                    'title' => 'Menu',
                    'items' => [
                        ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/admin/dashboard'],
                        [
                            'icon' => 'company',
                            'name' => 'Companies',
                            'subItems' => [
                                ['name' => 'All Companies', 'path' => '/admin/companies'],
                                ['name' => 'Create Company', 'path' => '/admin/companies/create'],
                            ],
                        ],
                        ['icon' => 'users', 'name' => 'Users', 'path' => '/admin/users'],
                        ['icon' => 'ai-brain', 'name' => 'AI Insights', 'path' => '/admin/ai-insights'],
                        ['icon' => 'pages', 'name' => 'Landing CMS', 'path' => '/admin/landing-page'],
                        ['icon' => 'pipeline', 'name' => 'Activity', 'path' => '/admin/activity'],
                    ],
                ],
                [
                    'title' => 'Account',
                    'items' => [
                        ['icon' => 'user-profile', 'name' => 'My Profile', 'path' => '/profile'],
                        ['icon' => 'settings', 'name' => 'Account Settings', 'path' => '/account/settings'],
                    ],
                ],
            ];
        }

        // HR Admin
        if ($user->hasRole('hr_admin')) {
            return [
                [
                    'title' => 'Menu',
                    'items' => [
                        ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/recruiter/dashboard'],
                        [
                            'icon' => 'briefcase',
                            'name' => 'Jobs',
                            'subItems' => [
                                ['name' => 'All Jobs', 'path' => '/recruiter/jobs'],
                                ['name' => 'Post New Job', 'path' => '/recruiter/jobs/create'],
                            ],
                        ],
                        ['icon' => 'users', 'name' => 'Candidates', 'path' => '/recruiter/candidates'],
                        ['icon' => 'pipeline', 'name' => 'Applications', 'path' => '/recruiter/applications'],
                        ['icon' => 'calendar', 'name' => 'Interview Slots', 'path' => '/recruiter/interview-slots'],
                        ['icon' => 'ai-brain', 'name' => 'AI Screening', 'path' => '/recruiter/ai/screen'],
                    ],
                ],
                [
                    'title' => 'Settings',
                    'items' => [
                        ['icon' => 'settings', 'name' => 'Company Settings', 'path' => '/recruiter/settings'],
                        ['icon' => 'user-profile', 'name' => 'My Profile', 'path' => '/profile'],
                        ['icon' => 'settings', 'name' => 'Account Settings', 'path' => '/account/settings'],
                    ],
                ],
            ];
        }

        // HR Standard
        if ($user->hasRole('hr_standard')) {
            return [
                [
                    'title' => 'Menu',
                    'items' => [
                        ['icon' => 'pipeline', 'name' => 'Applications', 'path' => '/recruiter/applications'],
                    ],
                ],
                [
                    'title' => 'Settings',
                    'items' => [
                        ['icon' => 'user-profile', 'name' => 'My Profile', 'path' => '/profile'],
                        ['icon' => 'settings', 'name' => 'Account Settings', 'path' => '/account/settings'],
                    ],
                ],
            ];
        }

        // Hiring Manager
        if ($user->hasRole('hiring_manager')) {
            return [
                [
                    'title' => 'Menu',
                    'items' => [
                        ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/manager/dashboard'],
                        ['icon' => 'users', 'name' => 'Shortlisted', 'path' => '/manager/shortlisted'],
                    ],
                ],
                [
                    'title' => 'Settings',
                    'items' => [
                        ['icon' => 'user-profile', 'name' => 'My Profile', 'path' => '/profile'],
                        ['icon' => 'settings', 'name' => 'Account Settings', 'path' => '/account/settings'],
                    ],
                ],
            ];
        }

        // Candidate
        if ($user->hasRole('candidate')) {
            return [
                [
                    'title' => 'Menu',
                    'items' => [
                        ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/candidate/dashboard'],
                        ['icon' => 'briefcase', 'name' => 'Browse Jobs', 'path' => '/candidate/jobs'],
                        ['icon' => 'pipeline', 'name' => 'My Applications', 'path' => '/candidate/applications'],
                    ],
                ],
                [
                    'title' => 'Settings',
                    'items' => [
                        ['icon' => 'user-profile', 'name' => 'My Profile', 'path' => '/candidate/my-profile'],
                        ['icon' => 'settings', 'name' => 'Account Settings', 'path' => '/account/settings'],
                    ],
                ],
            ];
        }

        // Fallback
        return [['title' => 'Menu', 'items' => [['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => '/']]]];
    }

    public static function isActive($path)
    {
        return request()->is(ltrim($path, '/'));
    }

    public static function getIconSvg($iconName)
    {
        $icons = [
            'dashboard' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z" fill="currentColor"></path></svg>',

            'briefcase' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.25 3.25C8.00736 3.25 7 4.25736 7 5.5V6.25H5C3.75736 6.25 2.75 7.25736 2.75 8.5V18.5C2.75 19.7426 3.75736 20.75 5 20.75H19C20.2426 20.75 21.25 19.7426 21.25 18.5V8.5C21.25 7.25736 20.2426 6.25 19 6.25H17V5.5C17 4.25736 15.9926 3.25 14.75 3.25H9.25ZM15.5 6.25V5.5C15.5 5.08579 15.1642 4.75 14.75 4.75H9.25C8.83579 4.75 8.5 5.08579 8.5 5.5V6.25H15.5ZM4.25 8.5C4.25 8.08579 4.58579 7.75 5 7.75H19C19.4142 7.75 19.75 8.08579 19.75 8.5V12.25H4.25V8.5ZM4.25 13.75V18.5C4.25 18.9142 4.58579 19.25 5 19.25H19C19.4142 19.25 19.75 18.9142 19.75 18.5V13.75H4.25Z" fill="currentColor"/></svg>',

            'users' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9 3.5C7.067 3.5 5.5 5.067 5.5 7C5.5 8.933 7.067 10.5 9 10.5C10.933 10.5 12.5 8.933 12.5 7C12.5 5.067 10.933 3.5 9 3.5ZM7 7C7 5.89543 7.89543 5 9 5C10.1046 5 11 5.89543 11 7C11 8.10457 10.1046 9 9 9C7.89543 9 7 8.10457 7 7ZM15 5.5C15 5.08579 15.3358 4.75 15.75 4.75C17.2688 4.75 18.5 5.98122 18.5 7.5C18.5 9.01878 17.2688 10.25 15.75 10.25C15.3358 10.25 15 9.91421 15 9.5C15 9.08579 15.3358 8.75 15.75 8.75C16.4404 8.75 17 8.19036 17 7.5C17 6.80964 16.4404 6.25 15.75 6.25C15.3358 6.25 15 5.91421 15 5.5ZM3.25 18C3.25 15.3766 5.37665 13.25 8 13.25H10C12.6234 13.25 14.75 15.3766 14.75 18V19C14.75 19.4142 14.4142 19.75 14 19.75C13.5858 19.75 13.25 19.4142 13.25 19V18C13.25 16.2051 11.7949 14.75 10 14.75H8C6.20507 14.75 4.75 16.2051 4.75 18V19C4.75 19.4142 4.41421 19.75 4 19.75C3.58579 19.75 3.25 19.4142 3.25 19V18ZM16.75 13.75C16.3358 13.75 16 14.0858 16 14.5C16 14.9142 16.3358 15.25 16.75 15.25C17.9926 15.25 19 16.2574 19 17.5V19C19 19.4142 19.3358 19.75 19.75 19.75C20.1642 19.75 20.5 19.4142 20.5 19V17.5C20.5 15.4289 18.8211 13.75 16.75 13.75Z" fill="currentColor"/></svg>',

            'pipeline' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.25 4C3.25 3.58579 3.58579 3.25 4 3.25H10C10.4142 3.25 10.75 3.58579 10.75 4V9C10.75 9.41421 10.4142 9.75 10 9.75H4C3.58579 9.75 3.25 9.41421 3.25 9V4ZM4.75 4.75V8.25H9.25V4.75H4.75ZM13.25 4C13.25 3.58579 13.5858 3.25 14 3.25H20C20.4142 3.25 20.75 3.58579 20.75 4V9C20.75 9.41421 20.4142 9.75 20 9.75H14C13.5858 9.75 13.25 9.41421 13.25 9V4ZM14.75 4.75V8.25H19.25V4.75H14.75ZM3.25 15C3.25 14.5858 3.58579 14.25 4 14.25H10C10.4142 14.25 10.75 14.5858 10.75 15V20C10.75 20.4142 10.4142 20.75 10 20.75H4C3.58579 20.75 3.25 20.4142 3.25 20V15ZM4.75 15.75V19.25H9.25V15.75H4.75ZM13.25 15C13.25 14.5858 13.5858 14.25 14 14.25H20C20.4142 14.25 20.75 14.5858 20.75 15V20C20.75 20.4142 20.4142 20.75 20 20.75H14C13.5858 20.75 13.25 20.4142 13.25 20V15ZM14.75 15.75V19.25H19.25V15.75H14.75Z" fill="currentColor"/></svg>',

            'ai-brain' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="currentColor"/></svg>',

            'company' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.75 3.25C4.75 2.83579 5.08579 2.5 5.5 2.5H14.5C14.9142 2.5 15.25 2.83579 15.25 3.25V7.25H18.5C18.9142 7.25 19.25 7.58579 19.25 8V20.75H20C20.4142 20.75 20.75 21.0858 20.75 21.5C20.75 21.9142 20.4142 22.25 20 22.25H4C3.58579 22.25 3.25 21.9142 3.25 21.5C3.25 21.0858 3.58579 20.75 4 20.75H4.75V3.25ZM6.25 4V20.75H13.75V4H6.25ZM15.25 8.75V20.75H17.75V8.75H15.25ZM8 7.25C8 6.83579 8.33579 6.5 8.75 6.5H11.25C11.6642 6.5 12 6.83579 12 7.25C12 7.66421 11.6642 8 11.25 8H8.75C8.33579 8 8 7.66421 8 7.25ZM8 10.75C8 10.3358 8.33579 10 8.75 10H11.25C11.6642 10 12 10.3358 12 10.75C12 11.1642 11.6642 11.5 11.25 11.5H8.75C8.33579 11.5 8 11.1642 8 10.75ZM8 14.25C8 13.8358 8.33579 13.5 8.75 13.5H11.25C11.6642 13.5 12 13.8358 12 14.25C12 14.6642 11.6642 15 11.25 15H8.75C8.33579 15 8 14.6642 8 14.25Z" fill="currentColor"/></svg>',

            'settings' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.4858 3.5L13.5182 3.5C13.9233 3.5 14.2518 3.82851 14.2518 4.23377C14.2518 5.9529 16.1129 7.02795 17.602 6.1682C17.9528 5.96567 18.4014 6.08586 18.6039 6.43667L20.1203 9.0631C20.3229 9.41407 20.2027 9.86286 19.8517 10.0655C18.3625 10.9253 18.3625 13.0747 19.8517 13.9345C20.2026 14.1372 20.3229 14.5859 20.1203 14.9369L18.6039 17.5634C18.4013 17.9142 17.9528 18.0344 17.602 17.8318C16.1129 16.9721 14.2518 18.0471 14.2518 19.7663C14.2518 20.1715 13.9233 20.5 13.5182 20.5H10.4858C10.0804 20.5 9.75182 20.1714 9.75182 19.766C9.75182 18.0461 7.88983 16.9717 6.40067 17.8314C6.04945 18.0342 5.60037 17.9139 5.39767 17.5628L3.88167 14.937C3.67903 14.586 3.79928 14.1372 4.15026 13.9346C5.63949 13.0748 5.63946 10.9253 4.15025 10.0655C3.79926 9.86282 3.67901 9.41401 3.88165 9.06303L5.39764 6.43725C5.60034 6.08617 6.04943 5.96581 6.40065 6.16858C7.88982 7.02836 9.75182 5.9539 9.75182 4.23399C9.75182 3.82862 10.0804 3.5 10.4858 3.5ZM12.0009 8.16493C9.88289 8.16493 8.1659 9.88191 8.1659 11.9999C8.1659 14.1179 9.88289 15.8349 12.0009 15.8349C14.1189 15.8349 15.8359 14.1179 15.8359 11.9999C15.8359 9.88191 14.1189 8.16493 12.0009 8.16493Z" fill="currentColor"/></svg>',

            'user-profile' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.5C7.30558 3.5 3.5 7.30558 3.5 12C3.5 14.1526 4.3002 16.1184 5.61936 17.616C6.17279 15.3096 8.24852 13.5955 10.7246 13.5955H13.2746C15.7509 13.5955 17.8268 15.31 18.38 17.6167C19.6996 16.119 20.5 14.153 20.5 12C20.5 7.30558 16.6944 3.5 12 3.5ZM17.0246 18.8566V18.8455C17.0246 16.7744 15.3457 15.0955 13.2746 15.0955H10.7246C8.65354 15.0955 6.97461 16.7744 6.97461 18.8455V18.856C8.38223 19.8895 10.1198 20.5 12 20.5C13.8798 20.5 15.6171 19.8898 17.0246 18.8566ZM2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12ZM11.9991 7.25C10.8847 7.25 9.98126 8.15342 9.98126 9.26784C9.98126 10.3823 10.8847 11.2857 11.9991 11.2857C13.1135 11.2857 14.0169 10.3823 14.0169 9.26784C14.0169 8.15342 13.1135 7.25 11.9991 7.25Z" fill="currentColor"></path></svg>',

            // Legacy icons kept for backward compatibility
            'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2Z" fill="currentColor"></path></svg>',

            'forms' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H18.5001C19.7427 20.75 20.7501 19.7426 20.7501 18.5V5.5C20.7501 4.25736 19.7427 3.25 18.5001 3.25H5.5Z" fill="currentColor"></path></svg>',

            'tables' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.25 5.5C3.25 4.25736 4.25736 3.25 5.5 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V18.5C20.75 19.7426 19.7426 20.75 18.5 20.75H5.5C4.25736 20.75 3.25 19.7426 3.25 18.5V5.5Z" fill="currentColor"></path></svg>',

            'pages' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.50391 4.25C8.50391 3.83579 8.83969 3.5 9.25391 3.5H15.2777C15.4766 3.5 15.6674 3.57902 15.8081 3.71967L18.2807 6.19234C18.4214 6.333 18.5004 6.52376 18.5004 6.72268V16.75C18.5004 17.1642 18.1646 17.5 17.7504 17.5H9.25391C8.83969 17.5 8.50391 17.1642 8.50391 16.75V4.25Z" fill="currentColor"></path></svg>',

            'charts' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.00002 12.0957C4.00002 7.67742 7.58174 4.0957 12 4.0957C16.4183 4.0957 20 7.67742 20 12.0957C20 16.514 16.4183 20.0957 12 20.0957H5.06068L6.34317 18.8132Z" fill="currentColor"></path></svg>',

            'ui-elements' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.665 3.75618C11.8762 3.65061 12.1247 3.65061 12.3358 3.75618L18.7807 6.97853L12.3358 10.2009C12.1247 10.3064 11.8762 10.3064 11.665 10.2009L5.22014 6.97853L11.665 3.75618Z" fill="currentColor"></path></svg>',

            'authentication' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14 2.75C14 2.33579 14.3358 2 14.75 2C15.1642 2 15.5 2.33579 15.5 2.75V5.73291L17.75 5.73291H19C19.4142 5.73291 19.75 6.0687 19.75 6.48291C19.75 6.89712 19.4142 7.23291 19 7.23291H18.5L18.5 12.2329C18.5 15.5691 15.9866 18.3183 12.75 18.6901V21.25C12.75 21.6642 12.4142 22 12 22C11.5858 22 11.25 21.6642 11.25 21.25V18.6901C8.01342 18.3183 5.5 15.5691 5.5 12.2329L5.5 7.23291H5C4.58579 7.23291 4.25 6.89712 4.25 6.48291C4.25 6.0687 4.58579 5.73291 5 5.73291L6.25 5.73291L8.5 5.73291L8.5 2.75C8.5 2.33579 8.83579 2 9.25 2C9.66421 2 10 2.33579 10 2.75L10 5.73291L14 5.73291V2.75Z" fill="currentColor"/></svg>',
        ];

        return $icons[$iconName] ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/></svg>';
    }
}
