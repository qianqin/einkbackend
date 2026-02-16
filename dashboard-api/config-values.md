# Configuration Values (Berlin)

| Field | Value |
|-------|-------|
| lat | `52.52` |
| lon | `13.41` |
| tz | `Europe/Berlin` |
| p1_name | `Emma` |
| p1_type | `kid` |
| p1_ics_1 | |
| p1_ics_2 | |
| p1_ics_3 | |
| p1_timetable | *(see below)* |
| p2_name | `Leo` |
| p2_type | `kid` |
| p2_ics_1 | |
| p2_ics_2 | |
| p2_ics_3 | |
| p2_timetable | *(see below)* |
| p3_name | `Alice` |
| p3_type | `adult` |
| p3_ics_1 | *(your Google Calendar ICS URL)* |
| p3_ics_2 | *(optional second calendar)* |
| p3_ics_3 | |
| p3_timetable | |
| p4_name | `Bob` |
| p4_type | `adult` |
| p4_ics_1 | *(your Google Calendar ICS URL)* |
| p4_ics_2 | |
| p4_ics_3 | |
| p4_timetable | |

## Timetable JSON

### Person 1 (Emma) — paste into p1_timetable:

```
{"monday":["Math","German","English","PE","Music","Art"],"tuesday":["German","Math","Science","Art","PE","Music"],"wednesday":["English","Math","German","Music"],"thursday":["PE","Science","German","Math","English","Art"],"friday":["Math","German","Music","PE"]}
```

### Person 2 (Leo) — paste into p2_timetable:

```
{"monday":["German","Math","Science","PE","Art","Music"],"tuesday":["Math","German","English","Music","Science","PE"],"wednesday":["Science","German","Math","Art"],"thursday":["German","Math","PE","English","Music","Art"],"friday":["English","Math","German","Science"]}
```

## Polling URL

```
https://api.brightsky.dev/current_weather?lat={{lat}}&lon={{lon}}&tz={{tz}}
{{p1_ics_1}}
{{p1_ics_2}}
{{p1_ics_3}}
{{p2_ics_1}}
{{p2_ics_2}}
{{p2_ics_3}}
{{p3_ics_1}}
{{p3_ics_2}}
{{p3_ics_3}}
{{p4_ics_1}}
{{p4_ics_2}}
{{p4_ics_3}}
```
