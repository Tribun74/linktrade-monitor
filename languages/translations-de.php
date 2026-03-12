<?php
/**
 * German translations for Linktrade Monitor
 * Fallback when .mo file is not available
 *
 * @package Linktrade_Monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	// Plugin action links
	'Settings' => 'Einstellungen',
	'Once Monthly' => 'Einmal monatlich',

	// Admin Menu
	'Linktrade Monitor' => 'Linktrade Monitor',
	'Linktrade' => 'Linktrade',

	// Admin JS strings
	'Really delete this link?' => 'Diesen Link wirklich löschen?',
	'Saving...' => 'Speichere...',
	'Saved!' => 'Gespeichert!',
	'An error occurred' => 'Ein Fehler ist aufgetreten',

	// Header
	'New Link' => 'Neuer Link',

	// Check frequency
	'Once a Month' => 'Einmal monatlich',
	'Link checked.' => 'Link geprüft.',
	'Backlink checked.' => 'Backlink geprüft.',

	// Tabs
	'Dashboard' => 'Dashboard',
	'All Links' => 'Alle Links',
	'Fairness' => 'Fairness',
	'New' => 'Neu',

	// Dashboard
	'Total Links' => 'Gesamte Links',
	'Online' => 'Online',
	'active' => 'aktiv',
	'Warnings' => 'Warnungen',
	'Offline / Problems' => 'Offline / Probleme',
	'By Category' => 'Nach Kategorie',
	'Link Exchange' => 'Linktausch',
	'Paid Links' => 'Gekaufte Links',
	'Free' => 'Kostenlos',
	'Average Domain Rating' => 'Durchschnittliches Domain Rating',
	'Recent Links' => 'Neueste Links',

	// Links tab
	'Link Overview' => 'Link-Übersicht',
	'Search links...' => 'Links durchsuchen...',
	'All Categories' => 'Alle Kategorien',
	'All Status' => 'Alle Status',
	'Warning' => 'Warnung',
	'Offline' => 'Offline',
	'Unchecked' => 'Ungeprüft',

	// Table headers
	'Partner' => 'Partner',
	'Category' => 'Kategorie',
	'Status' => 'Status',
	'Start / Expiration' => 'Start / Ablauf',
	'DR' => 'DR',
	'Last Check' => 'Letzte Prüfung',
	'Actions' => 'Aktionen',
	'No links found. Add your first link!' => 'Keine Links gefunden. Füge deinen ersten Link hinzu!',
	'ago' => 'her',
	'Never' => 'Nie',
	'Edit' => 'Bearbeiten',
	'Delete' => 'Löschen',

	// Categories
	'Exchange' => 'Tausch',
	'Paid' => 'Gekauft',

	// Fairness tab
	'Reciprocity Tracker' => 'Gegenseitigkeits-Tracker',
	'Monitor if both sides of link exchanges are being fair.' => 'Überwacht ob beide Seiten beim Linktausch fair bleiben.',
	'Fair (both online)' => 'Fair (beide online)',
	'Unfair (partner offline)' => 'Unfair (Partner offline)',
	'Warning (nofollow etc.)' => 'Warnung (nofollow etc.)',
	'Their Link to You' => 'Sein Link zu dir',
	'Your Link to Them' => 'Dein Gegenlink',
	'No link exchange partners found.' => 'Keine Linktausch-Partner gefunden.',
	'Not set' => 'Nicht eingetragen',

	// Timing fields
	'Start Date' => 'Startdatum',
	'When was the link placed?' => 'Wann wurde der Link gesetzt?',
	'Expiration Date' => 'Ablaufdatum',
	'Only for time-limited agreements. Leave empty for permanent links.' => 'Nur für befristete Vereinbarungen. Bei permanenten Links leer lassen.',
	'Expired' => 'Abgelaufen',
	'%d days left' => 'Noch %d Tage',
	'until' => 'bis',

	// Add form
	'Add New Link' => 'Neuen Link hinzufügen',
	'Partner Information' => 'Partner-Informationen',
	'Partner Name' => 'Partner-Name',
	'Contact (Email)' => 'Kontakt (E-Mail)',
	'Incoming Link (from partner to you)' => 'Eingehender Link (von Partner zu dir)',
	'Page URL where the link is' => 'Seiten-URL wo der Link ist',
	'Your URL being linked' => 'Deine verlinkte URL',
	'Anchor Text' => 'Ankertext',
	'Reciprocal Link (your link to partner)' => 'Gegenlink (dein Link zum Partner)',
	'Your page with link to partner' => 'Deine Seite mit Link zum Partner',
	'Partner URL you link to' => 'Partner-URL die du verlinkst',
	'Anchor Text (your link)' => 'Ankertext (dein Link)',
	'e.g. Partner Name' => 'z.B. Partner-Name',
	'Additional Info (optional)' => 'Zusätzliche Infos (optional)',
	'Partner DR' => 'Partner DR',
	'My DR' => 'Mein DR',
	'Domain Rating for fairness comparison. Get DR from Ahrefs, Moz, or similar tools.' => 'Domain Rating für Fairness-Vergleich. DR von Ahrefs, Moz oder ähnlichen Tools.',
	'Notes' => 'Notizen',
	'Save Link' => 'Link speichern',

	// Compact form (v1.3.1)
	'Incoming Link' => 'Eingehender Link',
	'Backlink you receive' => 'Backlink den du erhältst',
	'Outgoing Link' => 'Ausgehender Link',
	'Link you give back' => 'Gegenlink den du gibst',
	'Partner Page URL' => 'Partner-Seiten-URL',
	'Your Linked URL' => 'Deine verlinkte URL',
	'Your Page URL' => 'Deine Seiten-URL',
	'Partner Target URL' => 'Partner-Ziel-URL',
	'Only for time-limited agreements' => 'Nur für befristete Vereinbarungen',
	'Domain Rating from Ahrefs, Moz, etc.' => 'Domain Rating von Ahrefs, Moz, etc.',
	'Additional notes...' => 'Zusätzliche Notizen...',
	'Additional notes about this link partnership...' => 'Zusätzliche Notizen zu dieser Link-Partnerschaft...',

	// Settings
	'Language' => 'Sprache',
	'Plugin Language' => 'Plugin-Sprache',
	'Choose the language for this plugin interface.' => 'Wähle die Sprache für die Plugin-Oberfläche.',
	'Notifications' => 'Benachrichtigungen',
	'Notification Email' => 'Benachrichtigungs-E-Mail',
	'Send email notifications for expiring links' => 'E-Mail-Benachrichtigungen für ablaufende Links senden',
	'Remind me X days before expiration' => 'Erinnere mich X Tage vor Ablauf',
	'Settings saved.' => 'Einstellungen gespeichert.',
	'Save Settings' => 'Einstellungen speichern',

	// Modal
	'Edit Link' => 'Link bearbeiten',

	// Status labels
	'N/A' => 'N/V',

	// AJAX messages
	'Permission denied.' => 'Zugriff verweigert.',
	'Link updated successfully.' => 'Link erfolgreich aktualisiert.',
	'Link saved successfully.' => 'Link erfolgreich gespeichert.',
	'Invalid link ID.' => 'Ungültige Link-ID.',
	'Link deleted.' => 'Link gelöscht.',
	'Link not found.' => 'Link nicht gefunden.',

	// Cron emails
	'[Linktrade Monitor] %d links expiring soon' => '[Linktrade Monitor] %d Links laufen bald ab',
	'The following links will expire in the next %d days:' => 'Folgende Links laufen in den nächsten %d Tagen ab:',
	'- %1$s: %2$s (%3$d days remaining)' => '- %1$s: %2$s (noch %3$d Tage)',

	// Link checker
	'Empty response from server' => 'Leere Antwort vom Server',
	'Link to target page not found' => 'Link zur Zielseite nicht gefunden',

	// Security
	'Security check failed.' => 'Sicherheitsprüfung fehlgeschlagen.',

	// Pro Card (Dashboard)
	'Upgrade to Pro for 10+ advanced features' => 'Upgrade auf Pro für 10+ erweiterte Features',
	'Project Management' => 'Projektverwaltung',
	'ROI Tracking & Analytics' => 'ROI-Tracking & Analysen',
	'Anchor Text Analysis' => 'Ankertext-Analyse',
	'Webhook & Slack Notifications' => 'Webhook- & Slack-Benachrichtigungen',
	'Configurable Check Frequency' => 'Einstellbare Prüffrequenz',
	'Tags & Link Organization' => 'Tags & Link-Organisation',

	// Pro hint
	'Go Pro' => 'Pro Version',
	'Need more features? Check out %1$sLinktrade Monitor Pro%2$s for project management, ROI tracking, webhooks, and 10+ advanced features.' => 'Mehr Features benötigt? Entdecke %1$sLinktrade Monitor Pro%2$s für Projektverwaltung, ROI-Tracking, Webhooks und 10+ erweiterte Features.',

	// Import/Export tab (v1.3.0)
	'Import/Export' => 'Import/Export',
	'Export Links' => 'Links exportieren',
	'Download all your links as a CSV file. You can use this for backup or to import into other tools.' => 'Lade alle deine Links als CSV-Datei herunter. Ideal für Backups oder Import in andere Tools.',
	'Export to CSV' => 'Als CSV exportieren',
	'Import Links' => 'Links importieren',
	'Import links from a CSV file. The file must use the exact column names shown below.' => 'Importiere Links aus einer CSV-Datei. Die Datei muss die unten gezeigten Spaltennamen verwenden.',
	'CSV File' => 'CSV-Datei',
	'Skip duplicate entries (based on partner_url)' => 'Duplikate überspringen (basierend auf partner_url)',
	'Import CSV' => 'CSV importieren',
	'CSV Field Reference' => 'CSV-Feld-Referenz',
	'Your CSV file must include a header row with these exact column names. Required fields are marked with *.' => 'Deine CSV-Datei muss eine Kopfzeile mit genau diesen Spaltennamen enthalten. Pflichtfelder sind mit * markiert.',
	'Column Name' => 'Spaltenname',
	'Required' => 'Pflicht',
	'Description' => 'Beschreibung',
	'Example' => 'Beispiel',
	'Name of the link partner or website' => 'Name des Linkpartners oder der Website',
	'URL of the page containing the backlink to you' => 'URL der Seite mit dem Backlink zu dir',
	'Your URL that receives the backlink' => 'Deine URL die den Backlink erhält',
	'Link type: exchange, paid, or free' => 'Linktyp: exchange, paid oder free',
	'Contact email of the partner' => 'Kontakt-E-Mail des Partners',
	'The clickable text of the backlink' => 'Der klickbare Text des Backlinks',
	'Your page containing the reciprocal link (for exchanges)' => 'Deine Seite mit dem Gegenlink (bei Tausch)',
	'Partner URL you link to (for exchanges)' => 'Partner-URL die du verlinkst (bei Tausch)',
	'Partner Domain Rating (0-100, from Ahrefs)' => 'Partner Domain Rating (0-100, von Ahrefs)',
	'Your Domain Rating (0-100)' => 'Dein Domain Rating (0-100)',
	'Date the link was placed (YYYY-MM-DD)' => 'Datum der Linksetzung (JJJJ-MM-TT)',
	'Expiration date for paid/timed links (YYYY-MM-DD)' => 'Ablaufdatum für gekaufte/befristete Links (JJJJ-MM-TT)',
	'Additional notes about this link' => 'Zusätzliche Notizen zu diesem Link',
	'Example CSV' => 'Beispiel-CSV',
	'No file uploaded.' => 'Keine Datei hochgeladen.',
	'Could not read file.' => 'Datei konnte nicht gelesen werden.',
	'CSV file must contain a header row and at least one data row.' => 'CSV-Datei muss eine Kopfzeile und mindestens eine Datenzeile enthalten.',
	'Required field missing: %s' => 'Pflichtfeld fehlt: %s',
	'Import complete: %1$d imported, %2$d skipped (duplicates), %3$d errors.' => 'Import abgeschlossen: %1$d importiert, %2$d übersprungen (Duplikate), %3$d Fehler.',
	'No links to export.' => 'Keine Links zum Exportieren.',

	// Health Score (v1.3.0)
	'Health' => 'Qualität',
	'Link Health Score' => 'Link-Qualitätswert',
);
