/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    // add other custom env vars here as needed
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
