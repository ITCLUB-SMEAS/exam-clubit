/**
 * Date formatting composable for consistent date/time display across the application
 */

/**
 * Format datetime to readable format like "17 December, 19:00 - 22:00"
 * @param {string} datetime - ISO datetime string
 * @param {object} options - Formatting options
 * @returns {string} Formatted datetime string
 */
export const formatDateTime = (datetime, options = {}) => {
    if (!datetime) return '-';

    const date = new Date(datetime);

    // Default options for Indonesian locale
    const defaultOptions = {
        day: 'numeric',
        month: 'long',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        ...options
    };

    return date.toLocaleString('id-ID', defaultOptions);
};

/**
 * Format datetime range like "17 December, 19:00 - 22:00"
 * @param {string} startTime - Start datetime ISO string
 * @param {string} endTime - End datetime ISO string
 * @returns {string} Formatted datetime range
 */
export const formatDateTimeRange = (startTime, endTime) => {
    if (!startTime || !endTime) return '-';

    const startDate = new Date(startTime);
    const endDate = new Date(endTime);

    const dateOptions = { day: 'numeric', month: 'long' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: false };

    const formattedDate = startDate.toLocaleDateString('id-ID', dateOptions);
    const formattedStartTime = startDate.toLocaleTimeString('id-ID', timeOptions);
    const formattedEndTime = endDate.toLocaleTimeString('id-ID', timeOptions);

    // Check if same day
    if (startDate.toDateString() === endDate.toDateString()) {
        return `${formattedDate}, ${formattedStartTime} - ${formattedEndTime}`;
    }

    // Different days
    const endFormattedDate = endDate.toLocaleDateString('id-ID', dateOptions);
    return `${formattedDate}, ${formattedStartTime} - ${endFormattedDate}, ${formattedEndTime}`;
};

/**
 * Format date only like "17 December 2024"
 * @param {string} datetime - ISO datetime string
 * @returns {string} Formatted date string
 */
export const formatDate = (datetime) => {
    if (!datetime) return '-';

    const date = new Date(datetime);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

/**
 * Format time only like "19:00"
 * @param {string} datetime - ISO datetime string
 * @returns {string} Formatted time string
 */
export const formatTime = (datetime) => {
    if (!datetime) return '-';

    const date = new Date(datetime);
    return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
};

/**
 * Vue composable for date formatting
 */
export const useDateFormat = () => {
    return {
        formatDateTime,
        formatDateTimeRange,
        formatDate,
        formatTime
    };
};

export default useDateFormat;
