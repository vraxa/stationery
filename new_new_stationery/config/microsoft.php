<?php
// Microsoft Azure AD App Registration
// Setup steps:
//   1. Go to https://portal.azure.com > Azure Active Directory > App registrations > New registration
//   2. Name: Stationery Ordering System
//   3. Supported account types: Accounts in this organizational directory only (single tenant)
//   4. Redirect URI (Web): https://yourserver.com/stationery/api/callback.php
//   5. Under API permissions (Delegated): openid, profile, email, User.Read, Mail.Send, offline_access
//   6. Create a client secret under Certificates & secrets

return [
    "tenant_id" => "9431e0b9-00a8-40f7-a077-5f0920325f6c",
    "client_id" => "efb2cb7a-c639-4dc2-93db-f85ff21273bf",

    // Must exactly match the redirect URI registered in Azure
    "redirect_uri" =>
        "https://gc-az-intranet.grantham.ac.uk/new-stationery/api/callback.php",

    // Required scopes — do not change unless you know what you're doing
    "scopes" => "openid profile email User.Read Mail.Send offline_access",
];
