export function refreshDatePickers(container) {
  const dateEls = [...container.querySelectorAll(".field-type-date input")];
  const datetimeEls = [
    ...container.querySelectorAll(".field-type-datetime input"),
  ];
  const timeEls = [...container.querySelectorAll(".field-type-time input")];

  dateEls.forEach((dateEl) => createDatePicker(dateEl));

  datetimeEls.forEach((datetimeEl) =>
    createDatePicker(datetimeEl, { timepicker: true })
  );

  timeEls.forEach((timeEl) =>
    createDatePicker(timeEl, { timepicker: true, onlyTimepicker: true })
  );
}

export function createDatePicker(el, opts) {
  if (!el) return;

  if (el.datepicker) {
    el.datepicker.destroy();
  }

  el.datepicker = new AirDatepicker(el, {
    ...opts,
    locale: translatedLocale,
  });
}

// i18n
const locale = {
  days: [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ],
  months: [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ],
};

const getLocalizedWeekday = (weekday, format = "short") => {
  const date = new Date();
  date.setDate(date.getDate() + ((7 + weekday - date.getDay()) % 7));
  return date.toLocaleString(navigator.language, { weekday: format });
};

const getLocalizedMonth = (month, format = "short") => {
  const date = new Date();
  date.setMonth(month);
  return date.toLocaleString(navigator.language, { month: format });
};

const translatedLocale = {
  days: locale.days.map((_, index) => getLocalizedWeekday(index, "long")),
  daysShort: locale.days.map((_, index) => getLocalizedWeekday(index, "short")),
  daysMin: locale.days.map((_, index) => getLocalizedWeekday(index, "narrow")),
  months: locale.months.map((_, index) => getLocalizedMonth(index, "long")),
  monthsShort: locale.months.map((_, index) =>
    getLocalizedMonth(index, "short")
  ),
  today: "Today",
  clear: "Clear",
  dateFormat: "MM/dd/yyyy",
  timeFormat: "hh:mm aa",
  firstDay: 0,
};
