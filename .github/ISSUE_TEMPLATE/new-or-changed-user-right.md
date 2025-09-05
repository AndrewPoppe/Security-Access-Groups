---
name: New or Changed User Right
about: Use this form when a User Right has been added or changed in REDCap
title: "[NEW USER RIGHT]"
labels: ''
assignees: AndrewPoppe

---

**Description**
Briefly describe the change

**Is this a new or changed user right?**
- [ ] New user right
- [ ] Changed user right
- [ ] Removed user right

**When did this change happen?**
E.g., what REDCap version introduced the change?

**What changes to the EM need to be made to accommodate this change?**
Relevant changes

**What language strings are involved in the change?**
e.g.,
* `rights_449` = "Edit Survey Responses"
* `global_19` = "Delete"

**How are values stored/used?**
Basic 0/1 or something else?

**Checklist**

- [ ] Update UI
  - [ ] `SAGEditForm.php`
  - [ ] `RightsUtilities.php`
  - [ ] `system-settings-sags` (html, php, js)
- [ ] Rights checking
  - [ ] `RightsChecker.php`
- [ ] Update README
  - [ ] Update description of rights and values
  - [ ] Update relevant screenshots
  - [ ] Update translations
- [ ] Update Automated Testing scripts
