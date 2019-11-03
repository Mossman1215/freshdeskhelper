<?php
namespace freshdeskhelper;
use Exception;

class CredentialProvider
{
    const ENV_KEY = 'FRESHDESK_API_KEY';
    const ENV_ID = 'FRESHDESK_AGENT_ID';
    const ENV_TRIAGE = 'FRESHDESK_TRIAGE_ID';
    const ENV_PROFILE = 'default';
    /**
     * Get the default profile, either defined as ENV_PROFILE or
     * falling back to "default".
     *
     * @return string
     */
    public static function defaultProfile()
    {
        return getenv(self::ENV_PROFILE) ?: 'default';
    }

    /**
     * Provider that creates credentials from environment variables.
     *
     * @return null|array
     */
    public static function fromenv()
    {
        $secret = getenv(self::ENV_KEY);
        if ($secret) {
            return $secret;
        }
    }

    /**
     * Provider that creates credentials using an ini file stored
     * in the current user's home directory.
     *
     * @param null|string $profile  Profile to use. If not specified will use
     *                              the "default" profile in "~/.freshdeskhelper/credentials".
     * @param null|string $filename if provided, uses a custom filename rather
     *                              than looking in the home directory
     *
     * @throws Exception
     *
     * @return Credential
     */
    public static function fromini($profile = null, $filename = null)
    {
        $filename = $filename ?: sprintf('%s/.freshdeskhelper/credentials', self::getHomeDir());
        $profile = $profile ?: self::defaultProfile();

        if (!is_readable($filename)) {
            throw new Exception(sprintf('Cannot read credentials from %s', $filename));
        }

        $data = parse_ini_file($filename, true);
        if (false === $data) {
            throw new Exception(sprintf('Invalid credentials file: %s', $filename));
        }

        if (!isset($data[$profile])) {
            throw new Exception(sprintf('Profile "%s" not found in credentials file', $profile));
        }
        if (
            !isset($data[$profile]['freshdesk_agent_id'])
        ) {
            throw new Exception(sprintf('Profile "%s" is missing freshdesk_agent_id', $profile));
        }
        if (
            !isset($data[$profile]['freshdesk_api_key'])
        ) {
            throw new Exception(sprintf('Profile "%s" is missing freshdesk_api_key', $profile));
        }
        if (
            !isset($data[$profile]['freshdesk_triage_id'])
        ) {
            throw new Exception(sprintf('Profile "%s" is missing freshdesk_triage_id', $profile));
        }

        return [$data[$profile]['freshdesk_api_key'] , $data[$profile]['freshdesk_agent_id'],$data[$profile]['freshdesk_triage_id']];
    }

    /**
     * Gets the environment's HOME directory if available.
     *
     * @return null|string
     */
    private static function getHomeDir()
    {
        // On Linux/Unix-like systems, use the HOME environment variable
        if (getenv('HOME')) {
            return getenv('HOME');
        }

        // Get the HOMEDRIVE and HOMEPATH values for Windows hosts
        $homeDrive = getenv('HOMEDRIVE');
        $homePath = getenv('HOMEPATH');

        return ($homeDrive && $homePath) ? sprintf('%s%s', $homeDrive, $homePath) : null;
    }
}

